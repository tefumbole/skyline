<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Product;
use App\Unit;
use DB;
use Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $role = Role::find(Auth::user()->role_id);
        if($role->hasPermissionTo('category')) {
            $lims_categories = Category::where('is_active', true)->pluck('name', 'id');
            $lims_category_all = Category::where('is_active', true)->get();
            $inlineUnits = Unit::where('is_active', true)->whereNull('base_unit')->orderBy('unit_name')->get(['id', 'unit_name']);
            return view('category.create',compact('lims_categories', 'lims_category_all', 'inlineUnits'));
        }
        else
            return redirect()->back()->with('not_permitted', 'Sorry! You are not allowed to access this module');
    }

    public function categoryData(Request $request)
    {
        $columns = array(
            0 =>'id',
            2 =>'name',
            3=> 'parent_id',
            4=> 'is_active',
        );

        $totalData = Category::where('is_active', true)->count();
        $totalFiltered = $totalData;

        if($request->input('length') != -1)
            $limit = $request->input('length');
        else
            $limit = $totalData;
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if(empty($request->input('search.value')))
            $categories = Category::offset($start)
                        ->where('is_active', true)
                        ->limit($limit)
                        ->orderBy($order,$dir)
                        ->get();
        else
        {
            $search = $request->input('search.value');
            $categories =  Category::where([
                            ['name', 'LIKE', "%{$search}%"],
                            ['is_active', true]
                        ])->offset($start)
                        ->limit($limit)
                        ->orderBy($order,$dir)->get();

            $totalFiltered = Category::where([
                            ['name','LIKE',"%{$search}%"],
                            ['is_active', true]
                        ])->count();
        }
        $data = array();
        $unitNames = Unit::pluck('unit_name', 'id');
        if(!empty($categories))
        {
            foreach ($categories as $key=>$category)
            {
                $nestedData['id'] = $category->id;
                $nestedData['key'] = $key;

                $imageUrl = $category->image
                    ? \App\Support\PosProductImage::catalogUrl('category', $category->image)
                    : \App\Support\PosProductImage::url('zummXD2dvAtI.png');
                $nestedData['image'] = '<div class="w2k-paste" tabindex="0" data-paste-url="'.e(url('category/'.$category->id.'/paste-image')).'" title="Click, then paste an image"><img src="'.e($imageUrl).'" height="70" width="70"><span class="w2k-paste-label">Paste image</span></div>';

                $label = e($category->name).($category->department ? ' ('.e(ucfirst($category->department)).')' : '');
                $nestedData['name'] = $label;
                $nestedData['unit_id'] = $category->unit_id ? (int) $category->unit_id : '';
                $nestedData['unit'] = ($category->unit_id && isset($unitNames[$category->unit_id])) ? $unitNames[$category->unit_id] : 'N/A';

                $parent = $category->parent_id ? Category::find($category->parent_id) : null;
                $nestedData['parent_value'] = $parent ? (int) $parent->id : '';
                $nestedData['parent_id'] = $parent ? $parent->name : 'N/A';

                $productIds = $this->categoryTreeIds($category->id);
                $categoryProducts = Product::where('is_active', true)->whereIn('category_id', $productIds);
                $nestedData['number_of_product'] = '<a href="'.e(url('products?category_id='.$category->id)).'">'.(clone $categoryProducts)->count().'</a>';
                $nestedData['stock_qty'] = (clone $categoryProducts)->sum('qty');
                $total_price = (clone $categoryProducts)->sum(DB::raw('price * qty'));
                $total_cost = (clone $categoryProducts)->sum(DB::raw('cost * qty'));

                if(config('currency_position') == 'prefix')
                    $nestedData['stock_worth'] = config('currency').' '.$total_price.' / '.config('currency').' '.$total_cost;
                else
                    $nestedData['stock_worth'] = $total_price.' '.config('currency').' / '.$total_cost.' '.config('currency');

                if(auth()->user()->role_id == 1) {
                $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                              <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="'.$category->id.'" class="open-EditCategoryDialog btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="dripicons-document-edit"></i> '.trans("file.edit").'</button>
                                </li>
                                <li class="divider"></li>'.
                                \Form::open(["route" => ["category.destroy", $category->id], "method" => "DELETE"] ).'
                                <li>
                                  <button type="submit" class="btn btn-link" onclick="return confirmDelete()"><i class="dripicons-trash"></i> '.trans("file.delete").'</button>
                                </li>'.\Form::close().'
                            </ul>
                        </div>';
                } else {
                    $nestedData['options'] = '<div class="btn-group">
                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.trans("file.action").'
                              <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">
                                <li>
                                    <button type="button" data-id="'.$category->id.'" class="open-EditCategoryDialog btn btn-link" data-toggle="modal" data-target="#editModal" ><i class="dripicons-document-edit"></i> '.trans("file.edit").'</button>
                                </li>
                            </ul>
                        </div>';
                }
                $data[] = $nestedData;
            }
        }
        $json_data = array(
                    "draw"            => intval($request->input('draw')),
                    "recordsTotal"    => intval($totalData),
                    "recordsFiltered" => intval($totalFiltered),
                    "data"            => $data
                    );

        echo json_encode($json_data);
    }

    public function store(Request $request)
    {
        $request->name = preg_replace('/\s+/', ' ', $request->name);
        $this->validate($request, [
            'name' => [
                'max:255',
                    Rule::unique('categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        ]);
        $image = $request->image;
        if ($image) {
            $stored = \App\Support\PosProductImage::storeUpload($image, 'category');
            if ($stored) {
                $lims_category_data['image'] = $stored;
            }
        }
        $lims_category_data['name'] = $request->name;
        $lims_category_data['parent_id'] = $request->parent_id;
        $lims_category_data['unit_id'] = $request->unit_id ? (int) $request->unit_id : null;
        $lims_category_data['department'] = in_array($request->department, ['food', 'drinks'], true) ? $request->department : null;
        $lims_category_data['is_active'] = true;
        Category::create($lims_category_data);
        return redirect('category')->with('message', 'Category inserted successfully');
    }

    public function edit($id)
    {
        $lims_category_data = Category::findOrFail($id);
        $lims_parent_data = Category::where('id', $lims_category_data['parent_id'])->first();
        if($lims_parent_data)
            $lims_category_data['parent'] = $lims_parent_data['name'];
        return $lims_category_data;
    }

    public function update(Request $request)
    {
        $this->validate($request,[
            'name' => [
                'max:255',
                Rule::unique('categories')->ignore($request->category_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $input = $request->except('image');
        if (! in_array(isset($input['department']) ? $input['department'] : '', ['food', 'drinks'], true)) {
            $input['department'] = null;
        }
        $input['unit_id'] = ! empty($input['unit_id']) ? (int) $input['unit_id'] : null;
        $lims_category_data = Category::findOrFail($request->category_id);
        $image = $request->image;
        if ($image) {
            $stored = \App\Support\PosProductImage::storeUpload($image, 'category');
            if ($stored) {
                $previous = basename((string) $lims_category_data->image);
                $input['image'] = $stored;
                if ($previous !== '' && $previous !== $stored && $previous !== 'zummXD2dvAtI.png') {
                    $previousPath = public_path('images/category/'.$previous);
                    if (is_file($previousPath)) {
                        @unlink($previousPath);
                    }
                }
            }
        }
        $lims_category_data->update($input);
        if ($request->filled('unit_id')) {
            $this->applyCategoryUnit($lims_category_data, (int) $request->unit_id);
        }
        return redirect('category')->with('message', 'Category updated successfully');
    }

    public function import(Request $request)
    {
        //get file
        $upload=$request->file('file');
        $ext = pathinfo($upload->getClientOriginalName(), PATHINFO_EXTENSION);
        if($ext != 'csv')
            return redirect()->back()->with('not_permitted', 'Please upload a CSV file');
        $filename =  $upload->getClientOriginalName();
        $filePath=$upload->getRealPath();
        //open and read
        $file=fopen($filePath, 'r');
        $header= fgetcsv($file);
        $escapedHeader=[];
        //validate
        foreach ($header as $key => $value) {
            $lheader=strtolower($value);
            $escapedItem=preg_replace('/[^a-z]/', '', $lheader);
            array_push($escapedHeader, $escapedItem);
        }
        //looping through othe columns
        while($columns=fgetcsv($file))
        {
            if($columns[0]=="")
                continue;
            foreach ($columns as $key => $value) {
                $value=preg_replace('/\D/','',$value);
            }
            $data= array_combine($escapedHeader, $columns);
            $category = Category::firstOrNew(['name' => $data['name'], 'is_active' => true ]);
            if($data['parentcategory']){
                $parent_category = Category::firstOrNew(['name' => $data['parentcategory'], 'is_active' => true ]);
                $parent_id = $parent_category->id;
            }
            else
                $parent_id = null;

            $category->parent_id = $parent_id;
            $category->is_active = true;
            $category->save();
        }
        return redirect('category')->with('message', 'Category imported successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $category_id = $request['categoryIdArray'];
        foreach ($category_id as $id) {
            $lims_product_data = Product::where('category_id', $id)->get();
            foreach ($lims_product_data as $product_data) {
                $product_data->is_active = false;
                $product_data->save();
            }
            $lims_category_data = Category::findOrFail($id);
            if($lims_category_data->image)
                unlink('public/images/category/'.$lims_category_data->image);
            $lims_category_data->is_active = false;
            $lims_category_data->save();
        }
        return 'Category deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_category_data = Category::findOrFail($id);
        $lims_category_data->is_active = false;
        $lims_product_data = Product::where('category_id', $id)->get();
        foreach ($lims_product_data as $product_data) {
            $product_data->is_active = false;
            $product_data->save();
        }
        if($lims_category_data->image)
            unlink('public/images/category/'.$lims_category_data->image);
        $lims_category_data->save();
        return redirect('category')->with('not_permitted', 'Category deleted successfully');
    }

    public function quickUpdate(Request $request)
    {
        $role = Role::find(Auth::user()->role_id);
        if (! $role || ! $role->hasPermissionTo('category')) {
            return response()->json(['message' => 'You are not allowed to edit categories.'], 403);
        }

        $category = Category::where('is_active', true)->find($request->input('id'));
        if (! $category) {
            return response()->json(['message' => 'Category was not found.'], 404);
        }

        $field = $request->input('field');
        $value = $request->input('value');
        if ($field === 'name') {
            $value = trim(strip_tags((string) $value));
            if ($value === '') {
                return response()->json(['message' => 'Name cannot be empty.'], 422);
            }
            $taken = Category::where('is_active', true)->where('id', '!=', $category->id)->where('name', $value)->exists();
            if ($taken) {
                return response()->json(['message' => 'Another category already uses this name.'], 422);
            }
            $suffix = $category->department ? ' ('.ucfirst($category->department).')' : '';
            if ($suffix && substr($value, -strlen($suffix)) === $suffix) {
                $value = trim(substr($value, 0, -strlen($suffix)));
            }
            $category->name = $value;
            $category->save();
            $display = $category->name.($category->department ? ' ('.ucfirst($category->department).')' : '');
        } elseif ($field === 'unit_id') {
            $unitId = ($value === '' || $value === null) ? null : (int) $value;
            if ($unitId && ! Unit::where('is_active', true)->where('id', $unitId)->exists()) {
                return response()->json(['message' => 'Choose a unit from the list.'], 422);
            }
            $this->applyCategoryUnit($category, $unitId);
            $category->refresh();
            $display = $category->unit_id ? optional(Unit::find($category->unit_id))->unit_name : 'N/A';
        } elseif ($field === 'parent_id') {
            $parentId = ($value === '' || $value === null) ? null : (int) $value;
            if ($parentId) {
                if (! Category::where('is_active', true)->where('id', $parentId)->exists()) {
                    return response()->json(['message' => 'Choose a parent from the list.'], 422);
                }
                if (in_array($parentId, $this->categoryTreeIds($category->id), true)) {
                    return response()->json(['message' => 'Choose a parent that is not this category or one inside it.'], 422);
                }
            }
            $category->parent_id = $parentId;
            $category->save();
            $display = $parentId ? Category::where('id', $parentId)->value('name') : 'N/A';
        } else {
            return response()->json(['message' => 'That field cannot be edited here.'], 422);
        }

        return response()->json([
            'success' => true,
            'display' => $display,
            'unit_id' => $category->unit_id,
            'parent_id' => $category->parent_id,
        ]);
    }

    protected function applyCategoryUnit(Category $category, $unitId)
    {
        $category->unit_id = $unitId ?: null;
        $category->save();
        if (! $category->unit_id) {
            return;
        }

        Product::where('is_active', true)
            ->whereIn('category_id', $this->categoryTreeIds($category->id))
            ->update([
                'unit_id' => $category->unit_id,
                'sale_unit_id' => $category->unit_id,
                'purchase_unit_id' => $category->unit_id,
            ]);
    }

    public function pasteImage(Request $request, $id)
    {
        $role = Role::find(Auth::user()->role_id);
        if (! $role || ! $role->hasPermissionTo('category')) {
            return response()->json(['message' => 'You are not allowed to edit categories.'], 403);
        }
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp',
        ]);
        $category = Category::where('is_active', true)->findOrFail($id);
        $stored = \App\Support\PosProductImage::storeUpload($request->file('image'), 'category');
        if (! $stored) {
            return response()->json(['message' => 'Could not save that image.'], 422);
        }
        $category->image = $stored;
        $category->save();

        return response()->json([
            'url' => \App\Support\PosProductImage::catalogUrl('category', $stored),
        ]);
    }

    protected function categoryTreeIds($id)
    {
        $id = (int) $id;
        if ($id < 1) {
            return [];
        }
        $children = [];
        foreach (Category::where('is_active', true)->get(['id', 'parent_id']) as $row) {
            $children[(int) $row->parent_id][] = (int) $row->id;
        }
        $ids = [];
        $stack = [$id];
        while ($stack) {
            $current = array_pop($stack);
            if (isset($ids[$current])) {
                continue;
            }
            $ids[$current] = true;
            foreach ($children[$current] ?? [] as $child) {
                $stack[] = $child;
            }
        }

        return array_keys($ids);
    }
}
