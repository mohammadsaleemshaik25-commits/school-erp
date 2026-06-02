<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::query()
            ->with('classRoom')
            ->orderBy('class_id')
            ->orderBy('section_name')
            ->get();

        return view('sections.index', compact('sections'));
    }

    public function create()
    {
        $classes = ClassRoom::query()
            ->orderBy('display_order')
            ->get();

        return view('sections.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
            'section_name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('sections', 'section_name')->where('class_id', $request->class_id),
            ],
        ]);

        Section::create([
            'class_id' => $request->class_id,
            'section_name' => $request->section_name,
        ]);

        return redirect('/sections')->with('success', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        $classes = ClassRoom::query()
            ->orderBy('display_order')
            ->get();

        return view('sections.edit', compact('section', 'classes'));
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'class_id' => ['required', 'exists:classes,class_id'],
            'section_name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('sections', 'section_name')
                    ->where('class_id', $request->class_id)
                    ->ignore($section->section_id, 'section_id'),
            ],
        ]);

        $section->update([
            'class_id' => $request->class_id,
            'section_name' => $request->section_name,
        ]);

        return redirect('/sections')->with('success', 'Section updated successfully.');
    }
}
