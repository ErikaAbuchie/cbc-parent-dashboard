<?php

namespace App\Http\Controllers;

use App\Models\ParentAccess;
use App\Models\SchoolClass;
use App\Services\TeacherClassAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherStudentController extends Controller
{
    private function schoolClass(Request $request, int $classId): SchoolClass
    {
        return TeacherClassAccess::query($request->user())->findOrFail($classId);
    }

    public function index(Request $request, int $classId)
    {
        $class = $this->schoolClass($request, $classId);
        return response()->json(['students' => $class->students()->withTrashed()->orderBy('full_name')
            ->get(['id', 'full_name', 'admission_number', 'parent_phone_number', 'deleted_at'])]);
    }

    private function fields(Request $request, ?int $studentId = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'admission_number' => ['required', 'string', 'max:50', Rule::unique('students')->ignore($studentId)],
            'parent_phone_number' => [$studentId ? 'sometimes' : 'required', 'string', 'regex:/^\+254[17][0-9]{8}$/'],
        ]);
    }

    public function store(Request $request, int $classId)
    {
        $class = $this->schoolClass($request, $classId);
        $data = $this->fields($request);
        $student = DB::transaction(function () use ($class, $data) {
            SchoolClass::whereKey($class->id)->lockForUpdate()->firstOrFail();
            return $class->students()->create($data);
        });
        return response()->json(['message' => 'Student added.', 'student' => $student->only(['id', 'full_name', 'admission_number'])], 201);
    }

    public function update(Request $request, int $classId, int $studentId)
    {
        $class = $this->schoolClass($request, $classId);
        $student = $class->students()->findOrFail($studentId);
        $data = $this->fields($request, $student->id);
        DB::transaction(function () use ($class, $student, $data) {
            SchoolClass::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $student->refresh();
            abort_if($student->trashed(), 409, 'Student was removed. Reload the class.');
            $student->update($data);
        });
        return response()->json(['message' => 'Student updated.']);
    }

    public function destroy(Request $request, int $classId, int $studentId)
    {
        $class = $this->schoolClass($request, $classId);
        DB::transaction(function () use ($class, $studentId) {
            SchoolClass::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $student = $class->students()->findOrFail($studentId);
            ParentAccess::where('student_id', $student->id)->update(['is_active' => false]);
            $student->delete();
        });
        return response()->json(['message' => 'Student removed from the active roster. Assessment history was retained.']);
    }

    public function restore(Request $request, int $classId, int $studentId)
    {
        $class = $this->schoolClass($request, $classId);
        DB::transaction(function () use ($class, $studentId) {
            SchoolClass::whereKey($class->id)->lockForUpdate()->firstOrFail();
            $student = $class->students()->onlyTrashed()->findOrFail($studentId);
            $student->restore();
        });
        return response()->json(['message' => 'Student restored to the class.']);
    }
}
