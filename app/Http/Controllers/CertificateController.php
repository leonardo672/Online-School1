<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a listing of the certificates.
     */
    public function index()
    {
        $certificates = Certificate::all(); // Fetch all certificates
        return view('certificates.index', compact('certificates')); // Pass certificates data to the view
    }

    /**
     * Show the form for creating a new certificate.
     */
    public function create()
    {
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        
        return view('certificates.create', compact('users', 'courses'));
    }

    /**
     * Store a newly created certificate in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'certificate_code' => 'required|string|unique:certificates,certificate_code',
            'expires_at' => 'nullable|date',
        ]);

        // Create a new certificate
        Certificate::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'certificate_code' => $request->certificate_code,
            'issued_at' => now(),
            'expires_at' => $request->expires_at,
        ]);

        // Redirect to the certificates list with a success message
        return redirect()->route('certificates.index')->with('success', 'Certificate created successfully!');
    }

    /**
     * Display the specified certificate.
     */
    public function show(string $id)
    {
        $certificate = Certificate::findOrFail($id); // Find the certificate by id
        return view('certificates.show', compact('certificate')); // Pass the certificate data to the view
    }

    /**
     * Show the form for editing the specified certificate.
     */
    public function edit($id)
    {
        $certificate = Certificate::findOrFail($id); // Retrieve the certificate by ID
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        
        return view('certificates.edit', compact('certificate', 'users', 'courses'));
    }

    /**
     * Update the specified certificate in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'certificate_code' => 'required|string|unique:certificates,certificate_code,' . $id,
            'expires_at' => 'nullable|date',
        ]);

        // Update the certificate
        $certificate = Certificate::findOrFail($id);
        $certificate->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'certificate_code' => $request->certificate_code,
            'expires_at' => $request->expires_at,
        ]);

        // Redirect to the certificates list with a success message
        return redirect()->route('certificates.index')->with('success', 'Certificate updated successfully!');
    }

    /**
     * Remove the specified certificate from storage.
     */
    public function destroy(string $id)
    {
        $certificate = Certificate::findOrFail($id); // Find the certificate by id
        $certificate->delete(); // Delete the certificate

        return redirect()->route('certificates.index')->with('success', 'Certificate deleted successfully.');
    }
}