<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of the payments.
     */
    public function index(Request $request)
    {
        // Build query with filters for paginated results
        $query = Payment::with(['user', 'course'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('payment_method'), function ($q) use ($request) {
                $q->where('payment_method', $request->payment_method);
            })
            ->when($request->filled('user_id'), function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->filled('course_id'), function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('user', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%');
                })->orWhereHas('course', function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search . '%');
                })->orWhere('transaction_id', 'like', '%' . $request->search . '%');
            })
            ->latest();

        // Get paginated results
        $payments = $query->paginate(20)->appends($request->query());
        
        // Calculate statistics (use separate queries for PostgreSQL compatibility)
        $paymentsCount = Payment::count();
        $totalRevenue = Payment::sum('amount');
        $completedPayments = Payment::where('status', 'completed')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $failedPayments = Payment::where('status', 'failed')->count();
        $refundedPayments = Payment::where('status', 'refunded')->count();
        $latestPayment = Payment::with(['user', 'course'])->latest()->first();
        
        // Calculate average payment amount
        $averagePayment = $paymentsCount > 0 ? $totalRevenue / $paymentsCount : 0;
        
        // Get status distribution for chart - FIXED: Use DB raw query for PostgreSQL
        $statusDistribution = DB::table('payments')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Get payment method distribution
        $methodDistribution = DB::table('payments')
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('count', 'payment_method');
        
        // Get top users by total payments
        $topUsers = User::select('users.*')
            ->selectRaw('COUNT(payments.id) as payment_count')
            ->selectRaw('SUM(payments.amount) as total_spent')
            ->leftJoin('payments', 'users.id', '=', 'payments.user_id')
            ->groupBy('users.id')
            ->orderByDesc('total_spent')
            ->take(5)
            ->get();
        
        // Get top courses by revenue
        $topCourses = Course::select('courses.*')
            ->selectRaw('COUNT(payments.id) as payment_count')
            ->selectRaw('SUM(payments.amount) as total_revenue')
            ->leftJoin('payments', 'courses.id', '=', 'payments.course_id')
            ->groupBy('courses.id')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
        
        // Get recent payments for activity feed
        $recentPayments = Payment::with(['user', 'course'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get monthly revenue for chart (last 6 months)
        $monthlyRevenue = DB::table('payments')
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw("EXTRACT(MONTH FROM created_at) as month, EXTRACT(YEAR FROM created_at) as year, SUM(amount) as revenue")
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => date('F Y', mktime(0, 0, 0, $item->month, 1, $item->year)),
                    'revenue' => $item->revenue
                ];
            });
        
        // Get data for filters
        $users = User::all();
        $courses = Course::all();
        $statuses = Payment::STATUSES;
        $methods = Payment::METHODS;
        
        return view('payments.index', [
            'payments' => $payments,
            'users' => $users,
            'courses' => $courses,
            'statuses' => $statuses,
            'methods' => $methods,
            
            // Statistics
            'paymentsCount' => $paymentsCount,
            'totalRevenue' => $totalRevenue,
            'completedPayments' => $completedPayments,
            'pendingPayments' => $pendingPayments,
            'failedPayments' => $failedPayments,
            'refundedPayments' => $refundedPayments,
            'latestPayment' => $latestPayment,
            'averagePayment' => $averagePayment,
            
            // Distributions
            'statusDistribution' => $statusDistribution,
            'methodDistribution' => $methodDistribution,
            'monthlyRevenue' => $monthlyRevenue,
            
            // Leaderboards
            'topUsers' => $topUsers,
            'topCourses' => $topCourses,
            'recentPayments' => $recentPayments,
        ]);
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create()
    {
        $users = User::all();
        $courses = Course::all();
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
        $payment = Payment::with(['user', 'course'])->findOrFail($id);
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified payment.
     */
    public function edit($id)
    {
        $payment = Payment::findOrFail($id);
        $users = User::all();
        $courses = Course::all();
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
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }
    
    /**
     * Export payments to CSV
     */
    public function export(Request $request)
    {
        $payments = Payment::with(['user', 'course'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('start_date'), function ($q) use ($request) {
                $q->where('created_at', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($q) use ($request) {
                $q->where('created_at', '<=', $request->end_date);
            })
            ->get();
        
        $fileName = 'payments_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
        
        $columns = ['ID', 'User', 'Course', 'Amount', 'Status', 'Payment Method', 'Transaction ID', 'Created At'];
        
        $callback = function() use($payments, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            foreach ($payments as $payment) {
                $row = [
                    $payment->id,
                    $payment->user->name ?? 'N/A',
                    $payment->course->title ?? 'N/A',
                    $payment->amount,
                    $payment->status,
                    $payment->payment_method,
                    $payment->transaction_id,
                    $payment->created_at->format('Y-m-d H:i:s')
                ];
                
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}