<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index()
    {
        $payments = Payment::all(); // Fetch all payments
        return view('payments.index', compact('payments')); // Pass payments data to the view
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create()
    {
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        $statuses = Payment::STATUSES;
        $methods = Payment::METHODS;
        
        return view('payments.create', compact('users', 'courses', 'statuses', 'methods'));
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:' . implode(',', Payment::STATUSES),
            'payment_method' => 'required|in:' . implode(',', Payment::METHODS),
            'transaction_id' => 'required|string|unique:payments,transaction_id',
        ]);

        // Create a new payment
        Payment::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'amount' => $request->amount,
            'status' => $request->status,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
        ]);

        // Redirect to the payments list with a success message
        return redirect()->route('payments.index')->with('success', 'Payment created successfully!');
    }

    /**
     * Display the specified payment.
     */
    public function show(string $id)
    {
        $payment = Payment::findOrFail($id); // Find the payment by id
        return view('payments.show', compact('payment')); // Pass the payment data to the view
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit($id)
    {
        $payment = Payment::findOrFail($id); // Retrieve the payment by ID
        $users = User::all(); // Retrieve all users
        $courses = Course::all(); // Retrieve all courses
        $statuses = Payment::STATUSES;
        $methods = Payment::METHODS;
        
        return view('payments.edit', compact('payment', 'users', 'courses', 'statuses', 'methods'));
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, $id)
    {
        // Validate the request data
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|in:' . implode(',', Payment::STATUSES),
            'payment_method' => 'required|in:' . implode(',', Payment::METHODS),
            'transaction_id' => 'required|string|unique:payments,transaction_id,' . $id,
        ]);

        // Update the payment
        $payment = Payment::findOrFail($id);
        $payment->update([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'amount' => $request->amount,
            'status' => $request->status,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
        ]);

        // Redirect to the payments list with a success message
        return redirect()->route('payments.index')->with('success', 'Payment updated successfully!');
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id); // Find the payment by id
        $payment->delete(); // Delete the payment

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }
}