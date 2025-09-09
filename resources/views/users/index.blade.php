<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>All Users - MLM Tree</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
        }

        .main-content {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 16px 16px 0 0 !important;
            padding: 20px 24px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #f9fafb;
            border: none;
            font-weight: 600;
            color: #374151;
        }

        .table td {
            border: none;
            padding: 16px;
            vertical-align: middle;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .badge {
            border-radius: 8px;
            padding: 6px 12px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4 class="navbar-brand mb-0">
                            <i class="fas fa-tree me-2"></i>
                            MLM Tree
                        </h4>
                    </div>

                    <nav class="nav flex-column">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                        <a class="nav-link active" href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i>
                            All Users
                        </a>
                        <a class="nav-link" href="{{ route('add-user-form') }}">
                            <i class="fas fa-user-plus"></i>
                            Add User
                        </a>
                        <a class="nav-link" href="{{ route('users.tree', ['username' => 'admin']) }}">
                            <i class="fas fa-sitemap"></i>
                            Tree View
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-line"></i>
                            Reports
                        </a>
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h1 class="page-title">All Users</h1>
                                <p class="page-subtitle">Manage and view all users in your MLM network</p>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="{{ route('buy-slot-form') }}" class="btn btn-light btn-lg">
                                    <i class="fas fa-plus me-2"></i>
                                    Buy slot
                                </a>

                                <a href="http://127.0.0.1:8000/users/john/tree" class="btn btn-light btn-lg">
                                    <i class="fas fa-sitemap me-1"></i>
                                    John Tree
                                </a>
                                <a href="{{ route('add-user-form') }}" class="btn btn-light btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Add New User
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2 text-primary"></i>
                                Users List ({{ count($users) }} total)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>User</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Current Level</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $user)
                                        @php
                                        $userLevel = $user->treeEntry ? $user->treeEntry->level_number : 'No Level';
                                        $userLevelPrice = $user->treeEntry ? number_format($user->treeEntry->slot_price) . ' TRX' : '';
                                        @endphp
                                        <tr>
                                            <td>{{ $user->id }}</td>
                                            <td>
                                                <div class="user-avatar">
                                                    {{ strtoupper(substr($user->username, 0, 1)) }}
                                                </div>
                                            </td>
                                            <td>
                                                <strong>{{ $user->username }}</strong>
                                            </td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                @if($user->treeEntry)
                                                <span class="badge bg-success">Level {{ $userLevel }}</span>
                                                <br><small class="text-muted">{{ $userLevelPrice }}</small>
                                                @else
                                                <span class="badge bg-secondary">No Level</span>
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at->diffForHumans() }}</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('users.tree', ['username' => $user->username]) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-sitemap me-1"></i>
                                                        View Tree
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#buyLevelModal{{ $user->id }}">
                                                        <i class="fas fa-shopping-cart me-1"></i>
                                                        Buy Level
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Buy Level Plan Modals -->
    @foreach($users as $user)
    <div class="modal fade" id="buyLevelModal{{ $user->id }}" tabindex="-1" aria-labelledby="buyLevelModalLabel{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buyLevelModalLabel{{ $user->id }}">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Buy Level Plan for {{ $user->username }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="buyLevelForm{{ $user->id }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <div class="mb-3">
                            <label for="level_id{{ $user->id }}" class="form-label">Select Level Plan</label>
                            <select class="form-select" id="level_id{{ $user->id }}" name="level_id" required>
                                <option value="">Choose a level plan</option>
                                @foreach($levelPlans as $plan)
                                <option value="{{ $plan->id }}"
                                    @if(($user->treeEntry && $user->treeEntry->level_id == $plan->id) || (!($user->treeEntry) && $loop->first)) selected @endif>
                                    Level {{ $plan->level_number }} - {{ number_format($plan->price) }} TRX
                                </option>
                                @endforeach
                            </select>

                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> This will create or update the user's tree entry with the selected level plan.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning buy-level-btn" data-user-id="{{ $user->id }}">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Buy Level Plan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add event listeners for buy level buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.buy-level-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    buyLevelPlan(userId);
                });
            });
        });

        function buyLevelPlan(userId) {
            const form = document.getElementById('buyLevelForm' + userId);
            const formData = new FormData(form);

            // Show loading state
            const button = document.querySelector(`[data-user-id="${userId}"].buy-level-btn`);
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            button.disabled = true;

            fetch('{{ route("buy-level-plan") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        // alert('Level plan purchased successfully!');
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('buyLevelModal' + userId));
                        modal.hide();
                        // Reload the page to show updated information
                        // window.location.href = "http://127.0.0.1:8000/users/john/tree";
                        location.reload();

                    } else {
                        alert('Error: ' + (data.message || 'Something went wrong'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error: Something went wrong');
                })
                .finally(() => {
                    // Reset button state
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
        }
    </script>
</body>

</html>