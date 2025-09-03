<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Binary MLM Tree Overview - MLM Tree</title>
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
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
        }
        
        .tree-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }
        
        .tree-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .tree-owner {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .tree-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 2px solid #e5e7eb;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }
        
        .tree-level {
            margin-bottom: 15px;
        }
        
        .level-title {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .level-members {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .member-box {
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--dark-color);
            min-width: 60px;
            text-align: center;
        }
        
        .member-box.empty {
            border: 2px dashed #d1d5db;
            background: #f9fafb;
            color: #9ca3af;
        }
        
        .member-box.spillover {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
            border-color: var(--warning-color);
        }
        
        .spillover-section {
            background: #fffbeb;
            border: 2px solid var(--warning-color);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .spillover-title {
            background: var(--warning-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 12px;
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
        
        .tree-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .tree-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-tree {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-tree:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            color: white;
        }
        
        .btn-tree.btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }
        
        .btn-tree.btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }
        
        .overview-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .overview-stat {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        
        .overview-stat .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .overview-stat .number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .overview-stat .label {
            color: #6b7280;
            font-weight: 600;
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
                        <a class="nav-link" href="{{ route('users.index') }}">
                            <i class="fas fa-users"></i>
                            All Users
                        </a>
                        <a class="nav-link" href="{{ route('add-user-form') }}">
                            <i class="fas fa-user-plus"></i>
                            Add User
                        </a>
                        <a class="nav-link active" href="{{ route('tree.overview') }}">
                            <i class="fas fa-sitemap"></i>
                            Tree Overview
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
                            <div class="col-md-8">
                                <h1 class="page-title">Binary MLM Tree Overview</h1>
                                <p class="page-subtitle">Complete network structure with 30-member binary trees and spillover management</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="{{ route('add-user-form') }}" class="btn btn-light btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Add New User
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overview Statistics -->
                    <div class="overview-stats">
                        <div class="overview-stat">
                            <div class="icon text-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="number">{{ $totalUsers }}</div>
                            <div class="label">Total Users</div>
                        </div>
                        <div class="overview-stat">
                            <div class="icon text-success">
                                <i class="fas fa-tree"></i>
                            </div>
                            <div class="number">{{ $totalTrees }}</div>
                            <div class="label">Tree Owners</div>
                        </div>
                        <div class="overview-stat">
                            <div class="icon text-warning">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="number">{{ $totalSpillovers }}</div>
                            <div class="label">Spillover Slots</div>
                        </div>
                        <div class="overview-stat">
                            <div class="icon text-info">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="number">{{ $completeTrees }}</div>
                            <div class="label">Complete Trees (30+)</div>
                        </div>
                    </div>
                    
                    <!-- Tree Grid -->
                    <div class="tree-grid">
                        @foreach($trees as $tree)
                        <div class="tree-card">
                            <div class="tree-owner">
                                <h5 class="mb-1">{{ $tree['owner']->username }}</h5>
                                <small>Tree Owner</small>
                            </div>
                            
                            <div class="tree-stats">
                                <div class="stat-item">
                                    <div class="stat-number">{{ $tree['member_count'] }}</div>
                                    <div class="stat-label">Members</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">{{ count($tree['tree_rounds']) }}</div>
                                    <div class="stat-label">Rounds</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">{{ count($tree['spillover_slots']) }}</div>
                                    <div class="stat-label">Spillovers</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number">{{ $tree['member_count'] >= 30 ? 'Full' : 'Active' }}</div>
                                    <div class="stat-label">Status</div>
                                </div>
                            </div>
                            
                            <!-- Tree Structure Preview -->
                            <div class="tree-level">
                                <div class="level-title">Level 1 ({{ count(array_filter($tree['level1'])) }}/2)</div>
                                <div class="level-members">
                                    @for($i = 0; $i < 2; $i++)
                                        @if(isset($tree['level1'][$i]) && $tree['level1'][$i])
                                            <div class="member-box">{{ $tree['level1'][$i]->user->username }}</div>
                                        @else
                                            <div class="member-box empty">Empty</div>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            
                            <div class="tree-level">
                                <div class="level-title">Level 2 ({{ count(array_filter($tree['level2'])) }}/4)</div>
                                <div class="level-members">
                                    @for($i = 0; $i < 4; $i++)
                                        @if(isset($tree['level2'][$i]) && $tree['level2'][$i])
                                            <div class="member-box">{{ $tree['level2'][$i]->user->username }}</div>
                                        @else
                                            <div class="member-box empty">Empty</div>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                            
                            <!-- Spillover Section -->
                            @if(count($tree['spillover_slots']) > 0)
                            <div class="spillover-section">
                                <div class="spillover-title">
                                    <i class="fas fa-recycle me-2"></i>
                                    Spillover Slots ({{ count($tree['spillover_slots']) }})
                                </div>
                                <div class="level-members">
                                    @foreach($tree['spillover_slots'] as $spillover)
                                        <div class="member-box spillover">
                                            {{ $spillover->user->username }}<br>
                                            <small>R{{ $spillover->tree_round }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            <div class="tree-actions">
                                <a href="{{ route('users.tree', ['username' => $tree['owner']->username]) }}" class="btn-tree">
                                    <i class="fas fa-eye me-2"></i>
                                    View Full Tree
                                </a>
                                <a href="{{ route('api.users.tree.stats', ['username' => $tree['owner']->username]) }}" class="btn-tree btn-success" target="_blank">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Stats API
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    @if(count($trees) == 0)
                    <div class="stats-card text-center">
                        <i class="fas fa-tree fa-4x text-muted mb-3"></i>
                        <h3>No Trees Found</h3>
                        <p class="text-muted">Start by adding the first user to create a tree owner.</p>
                        <a href="{{ route('add-user-form') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            Add First User
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
