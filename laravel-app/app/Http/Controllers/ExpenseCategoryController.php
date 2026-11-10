<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ExpenseCategory;
use App\Support\ExpenseBoard;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $lims_expense_category_all = $this->orderedCategories();
        $stats = ExpenseBoard::categoryStats($lims_expense_category_all);
        $frames = ExpenseBoard::frameChoices();
        return view('expense_category.index', compact('lims_expense_category_all', 'stats', 'frames'));
    }

    public function create()
    {
        //
    }

    public function generateCode()
    {
        return $this->nextSequentialCode();
    }

    protected function orderedCategories()
    {
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $byParent = [];
        foreach ($categories as $category) {
            $byParent[(int) $category->parent_id][] = $category;
        }
        $ordered = [];
        $seen = [];
        $walk = function ($parentId, $depth) use (&$walk, &$ordered, &$seen, $byParent) {
            $kids = isset($byParent[$parentId]) ? $byParent[$parentId] : [];
            foreach ($kids as $kid) {
                if (isset($seen[$kid->id])) {
                    continue;
                }
                $seen[$kid->id] = true;
                $kid->tree_depth = $depth;
                $ordered[] = $kid;
                $walk((int) $kid->id, $depth + 1);
            }
        };
        $walk(0, 0);
        foreach ($categories as $category) {
            if (! isset($seen[$category->id])) {
                $category->tree_depth = 0;
                $ordered[] = $category;
            }
        }

        return $ordered;
    }

    protected function categoryFields(Request $request, $ignoreId = null)
    {
        $parent = (int) $request->input('parent_id');
        if ($parent < 1 || ($ignoreId && $parent === (int) $ignoreId)) {
            $parent = null;
        }
        if ($parent && $ignoreId && $this->createsLoop($parent, (int) $ignoreId)) {
            $parent = null;
        }
        $months = (int) $request->input('budget_months');
        if (! in_array($months, [1, 2, 3, 6, 12], true)) {
            $months = null;
        }

        return [
            'parent_id' => $parent,
            'budget_months' => $months,
            'budget_amount' => $request->filled('budget_amount') ? $request->input('budget_amount') : null,
        ];
    }

    protected function createsLoop($parentId, $categoryId)
    {
        $id = (int) $parentId;
        $guard = 0;
        while ($id && $guard < 12) {
            if ($id === (int) $categoryId) {
                return true;
            }
            $id = (int) ExpenseCategory::where('id', $id)->value('parent_id');
            $guard++;
        }

        return false;
    }

    protected function nextSequentialCode()
    {
        $codes = ExpenseCategory::pluck('code');
        $max = 0;
        foreach ($codes as $code) {
            $code = trim((string) $code);
            if (! preg_match('/^\d{1,4}$/', $code)) {
                continue;
            }
            $n = (int) $code;
            if ($n > $max) {
                $max = $n;
            }
        }

        return str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $code = trim((string) $request->get('code'));
        if ($code === '') {
            $code = $this->nextSequentialCode();
            $request->merge(['code' => $code]);
        }

        $this->validate($request, [
            'code' => [
                'required',
                'max:255',
                Rule::unique('expense_categories')->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ],
            'name' => 'required|string|max:255',
        ]);

        $data = $request->all();
        if (empty($data['is_active'])) {
            $data['is_active'] = 1;
        }
        $data = array_merge($data, $this->categoryFields($request));
        ExpenseCategory::create($data);
        return redirect('expense_categories')->with('message', 'Data inserted successfully');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $lims_expense_category_data = ExpenseCategory::find($id);
        return $lims_expense_category_data;
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'code' => [
                'max:255',
                    Rule::unique('expense_categories')->ignore($request->expense_category_id)->where(function ($query) {
                    return $query->where('is_active', 1);
                }),
            ]
        ]);

        $data = $request->all();
        $lims_expense_category_data = ExpenseCategory::find($data['expense_category_id']);
        $data = array_merge($data, $this->categoryFields($request, $lims_expense_category_data ? $lims_expense_category_data->id : null));
        if ($lims_expense_category_data) {
            $lims_expense_category_data->update($data);
        }
        return redirect('expense_categories')->with('message', 'Data updated successfully');
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
           $expense_category = ExpenseCategory::firstOrNew(['code' => $data['code'], 'is_active' => true ]);
           $expense_category->code = $data['code'];
           $expense_category->name = $data['name'];
           $expense_category->is_active = true;
           $expense_category->save();
        }
        return redirect('expense_categories')->with('message', 'ExpenseCategory imported successfully');
    }

    public function deleteBySelection(Request $request)
    {
        $expense_category_id = $request['expense_categoryIdArray'];
        foreach ($expense_category_id as $id) {
            $lims_expense_category_data = ExpenseCategory::find($id);
            $lims_expense_category_data->is_active = false;
            $lims_expense_category_data->save();
        }
        return 'Expense Category deleted successfully!';
    }

    public function destroy($id)
    {
        $lims_expense_category_data = ExpenseCategory::find($id);
        $lims_expense_category_data->is_active = false;
        $lims_expense_category_data->save();
        return redirect('expense_categories')->with('not_permitted', 'Data deleted successfully');
    }
}
