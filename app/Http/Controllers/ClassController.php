<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassRoom::query()
            ->withCount('sections')
            ->orderBy('display_order')
            ->get();

        return view('classes.index', compact('classes'));
    }

    public function create()
    {
        return view('classes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_name' => ['required', 'string', 'max:50', 'unique:classes,class_name'],
            'display_order' => ['required', 'integer', 'min:1', 'unique:classes,display_order'],
        ]);

        ClassRoom::create([
            'class_name' => $request->class_name,
            'display_order' => $request->display_order,
        ]);

        return redirect('/classes')->with('success', 'Class created successfully.');
    }

    public function edit(ClassRoom $class)
    {
        return view('classes.edit', compact('class'));
    }

    public function update(Request $request, ClassRoom $class)
    {
        $request->validate([
            'class_name' => ['required', 'string', 'max:50', 'unique:classes,class_name,' . $class->class_id . ',class_id'],
            'display_order' => ['required', 'integer', 'min:1', 'unique:classes,display_order,' . $class->class_id . ',class_id'],
        ]);

        $class->update([
            'class_name' => $request->class_name,
            'display_order' => $request->display_order,
        ]);

        return redirect('/classes')->with('success', 'Class updated successfully.');
    }
}
