<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLM Tree Dashboard</title>
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
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            margin: 8px 0;
        }
        
        .stats-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
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
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            font-size: 18px;
            opacity: 0.9;
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
                        <a class="nav-link active" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                        <a class="nav-link" href="{{ route('users.index') }}">
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
                    <!-- Welcome Section -->
                    <div class="welcome-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="welcome-title">Welcome to MLM Tree Dashboard</h1>
                                <p class="welcome-subtitle">Manage your network and track performance</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('add-user-form') }}" class="btn btn-light btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Add New User
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <div class="stats-number">{{ $totalUsers }}</div>
                                        <div class="stats-label">Total Users</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                    <div>
                                        <div class="stats-number">{{ $totalReferrals }}</div>
                                        <div class="stats-label">Total Referrals</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                                        <i class="fas fa-user-clock"></i>
                                    </div>
                                    <div>
                                        <div class="stats-number">{{ $activeUsers }}</div>
                                        <div class="stats-label">Active Users (30d)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="stats-card">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon me-3" style="background: linear-gradient(135deg, var(--danger-color), #dc2626);">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <div class="stats-number">{{ $pendingReferrals }}</div>
                                        <div class="stats-label">Pending Referrals</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Users and Top Sponsors -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-clock me-2 text-primary"></i>
                                        Recent Users
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Username</th>
                                                    <th>Joined</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentUsers as $user)
                                                <tr>
                                                    <td>
                                                        <div class="user-avatar">
                                                            {{ strtoupper(substr($user->username, 0, 1)) }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $user->username }}</strong>
                                                    </td>
                                                    <td>{{ $user->created_at->diffForHumans() }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-trophy me-2 text-warning"></i>
                                        Top Sponsors
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Sponsor</th>
                                                    <th>Referrals</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topSponsors as $sponsor)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar me-2">
                                                                {{ strtoupper(substr($sponsor->username, 0, 1)) }}
                                                            </div>
                                                            <strong>{{ $sponsor->username }}</strong>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $sponsor->referrals->count() }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Active</span>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
