<?php

namespace App\Http\Controllers;

use App\Course;
use App\CourseCertificate;
use App\CourseFeedback;
use App\CourseInvoice;
use App\Services\CourseManagerService;
use App\StudentProgress;
use App\TrainingRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;

class CourseManagerController extends Controller
{
    protected $courses;
    protected $all_permission = [];

    public function __construct(CourseManagerService $courses)
    {
        $this->courses = $courses;
        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                $role = Role::find(Auth::user()->role_id);
                if ($role) {
                    foreach (Role::findByName($role->name)->permissions as $permission) {
                        $this->all_permission[] = $permission->name;
                    }
                }
            }
            View::share('all_permission', $this->all_permission);

            return $next($request);
        });
    }

    protected function authorizeCourses($permission = 'courses.view')
    {
        if (in_array('courses_module', $this->all_permission, true)
            || in_array($permission, $this->all_permission, true)) {
            return;
        }
        abort(403, 'You are not allowed to access Course Management.');
    }

    public function index(Request $request)
    {
        $this->authorizeCourses('courses.view');
        $items = $this->courses->allCourses($request->get('q'));

        return view('course_manager.index', compact('items'));
    }

    public function create()
    {
        $this->authorizeCourses('courses.create');

        return view('course_manager.create');
    }

    public function store(Request $request)
    {
        $this->authorizeCourses('courses.create');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:100',
            'delivery_mode' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
            'curriculum' => 'nullable|string',
        ]);
        if (! empty($data['curriculum'])) {
            $sections = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['curriculum']))));
            $data['curriculum_json'] = json_encode(array_map(function ($s) {
                return ['title' => $s, 'items' => []];
            }, $sections));
        }
        $this->courses->createCourse($data);

        return redirect()->route('courses.index')->with('message', 'Course created. It will appear on the public Trainings page when active.');
    }

    public function edit($id)
    {
        $this->authorizeCourses('courses.update');
        $course = Course::findOrFail($id);

        return view('course_manager.edit', compact('course'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeCourses('courses.update');
        $course = Course::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string|max:100',
            'delivery_mode' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'status' => 'nullable|string|max:50',
            'curriculum' => 'nullable|string',
        ]);
        if (array_key_exists('curriculum', $data)) {
            $sections = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $data['curriculum']))));
            $data['curriculum_json'] = json_encode(array_map(function ($s) {
                return ['title' => $s, 'items' => []];
            }, $sections));
        }
        $this->courses->updateCourse($course, $data);

        return redirect()->route('courses.index')->with('message', 'Course updated.');
    }

    public function destroy($id)
    {
        $this->authorizeCourses('courses.delete');
        $this->courses->deleteCourse(Course::findOrFail($id));

        return back()->with('message', 'Course deleted.');
    }

    public function move(Request $request, $id)
    {
        $this->authorizeCourses('courses.update');
        $this->courses->moveCourse(Course::findOrFail($id), $request->get('direction', 'up'));

        return back();
    }

    public function reorder(Request $request)
    {
        $this->authorizeCourses('courses.update');
        $ids = $request->input('ids', []);
        if (! is_array($ids) || ! count($ids)) {
            return response()->json(['ok' => false, 'error' => 'No ids'], 422);
        }
        $this->courses->reorderCourses($ids);

        return response()->json(['ok' => true]);
    }

    public function cloneCourse($id)
    {
        $this->authorizeCourses('courses.create');
        $clone = $this->courses->cloneCourse(Course::findOrFail($id));

        return redirect()->route('courses.edit', $clone->id)
            ->with('message', 'Course cloned. Rename it and set status to Active when ready.');
    }

    public function registrations(Request $request)
    {
        $this->authorizeCourses('courses.view');
        $stats = $this->courses->registrationStats();
        $items = $this->courses->registrations($request->get('q'), $request->get('status'));

        return view('course_manager.registrations', compact('items', 'stats'));
    }

    public function updateRegistration(Request $request, $id)
    {
        $this->authorizeCourses('courses.update');
        $reg = TrainingRegistration::findOrFail($id);
        $this->courses->updateRegistration($reg, $request->only('status', 'payment_status'));

        return back()->with('message', 'Registration updated.');
    }

    public function invoices(Request $request)
    {
        $this->authorizeCourses('courses.view');
        $items = $this->courses->invoices($request->get('q'));

        return view('course_manager.invoices', compact('items'));
    }

    public function certificates(Request $request)
    {
        $this->authorizeCourses('courses.view');
        $items = $this->courses->certificates($request->get('q'));

        return view('course_manager.certificates', compact('items'));
    }

    public function revokeCertificate($id)
    {
        $this->authorizeCourses('courses.update');
        $this->courses->revokeCertificate(CourseCertificate::findOrFail($id));

        return back()->with('message', 'Certificate revoked.');
    }

    public function progress(Request $request)
    {
        $this->authorizeCourses('courses.view');
        $stats = $this->courses->progressStats();
        $items = $this->courses->progressList($request->get('q'));

        return view('course_manager.progress', compact('items', 'stats'));
    }

    public function updateProgress(Request $request, $id)
    {
        $this->authorizeCourses('courses.update');
        $this->courses->updateProgress(StudentProgress::findOrFail($id), $request->only('progress_percentage', 'status'));

        return back()->with('message', 'Progress updated.');
    }

    public function feedback(Request $request)
    {
        $this->authorizeCourses('courses.view');
        $courseList = Course::orderBy('name')->get(['id', 'name']);
        $items = $this->courses->feedbackList(
            $request->get('q'),
            $request->get('course_id'),
            $request->get('rating')
        );

        return view('course_manager.feedback', compact('items', 'courseList'));
    }

    public function destroyFeedback($id)
    {
        $this->authorizeCourses('courses.delete');
        $this->courses->deleteFeedback($id);

        return back()->with('message', 'Feedback deleted.');
    }

    public function courseFeedback($id)
    {
        $this->authorizeCourses('courses.view');
        $course = Course::findOrFail($id);
        $items = $this->courses->feedbackForCourse($id);

        return view('course_manager.course_feedback', compact('course', 'items'));
    }
}
