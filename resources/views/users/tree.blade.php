<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tree View - {{ $user->username }} - MLM Tree</title>
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

        .tree-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            overflow-x: auto;
        }

        .tree-level {
            text-align: center;
            margin-bottom: 30px;
        }

        .tree-level-title {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .user-box {
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 8px 12px;
            margin: 5px;
            display: inline-block;
            min-width: 80px;
            font-size: 12px;
            font-weight: 600;
            color: var(--dark-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .user-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background: var(--primary-color);
            color: white;
        }

        .user-box.owner {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-color: var(--success-color);
            font-size: 14px;
            padding: 12px 16px;
            min-width: 100px;
        }

        .user-box.empty {
            border: 2px dashed #d1d5db;
            background: #f9fafb;
            color: #9ca3af;
        }

        .user-box.spillover {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
            border-color: var(--warning-color);
        }

        .spillover-section {
            background: #fffbeb;
            border: 2px solid var(--warning-color);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
        }

        .spillover-title {
            background: var(--warning-color);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 14px;
        }

        .tree-connections {
            position: relative;
            height: 20px;
            margin: 10px 0;
        }

        .connection-line {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-color);
            opacity: 0.3;
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

        .level-1-container {
            display: flex;
            justify-content: center;
            gap: 200px;
            margin: 20px 0;
        }

        .level-2-container {
            display: flex;
            justify-content: center;
            gap: 100px;
            margin: 20px 0;
        }

        .level-3-container {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin: 20px 0;
        }

        .level-4-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
            max-width: 100%;
            margin: 20px 0;
        }

        .level-4-container .user-box {
            min-width: 70px;
            font-size: 11px;
            padding: 8px 10px;
        }

        /* Tree connection lines */
        .tree-level {
            position: relative;
        }

        .tree-level::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0.3;
        }

        .level-1-container::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0.3;
        }

        /* Better spacing and visual hierarchy */
        .tree-level-title {
            background: linear-gradient(135deg, #8b5cf6, #a855f7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
        }

        .user-box {
            background: white;
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 8px 12px;
            margin: 5px;
            display: inline-block;
            min-width: 80px;
            font-size: 12px;
            font-weight: 600;
            color: var(--dark-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .user-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            background: var(--primary-color);
            color: white;
        }

        .user-box.owner {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-color: var(--success-color);
            font-size: 16px;
            padding: 15px 20px;
            min-width: 120px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .user-box.empty {
            border: 2px dashed #d1d5db;
            background: #f9fafb;
            color: #9ca3af;
            cursor: default;
        }

        .user-box.empty:hover {
            transform: none;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            background: #f9fafb;
            color: #9ca3af;
        }

        .user-box.clickable {
            cursor: pointer;
        }

        .user-box.clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            background: var(--primary-color);
            color: white;
        }

        /* Round Tabs Styles */
        .round-tabs-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .round-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .round-tab {
            background: #f8fafc;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 15px 20px;
            text-decoration: none;
            color: #6b7280;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 120px;
            text-align: center;
        }

        .round-tab:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            text-decoration: none;
        }

        .round-tab.active {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-color: var(--success-color);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .round-tab.active:hover {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-color: var(--success-color);
        }

        .round-tab small {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 2px;
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
                        <a class="nav-link active" href="{{ route('users.tree', ['username' => 'admin']) }}">
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
                                <h1 class="page-title">Tree View: {{ $user->username }}</h1>
                                <p class="page-subtitle">
                                    Network structure and hierarchy | Round {{ $round }} | Members: {{ $tree['member_count'] }}/30 (4 levels only)
                                    @if($tree['member_count'] == 0)
                                    | <span class="text-warning">No downline members yet</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6 text-end">

                                <a href="http://127.0.0.1:8000/users" class="btn btn-light btn-lg">
                                    <i class="fas fa-users me-1"></i>
                                    All user
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

                    <!-- Round Tabs -->
                    @if(count($availableRounds) > 1)
                    <div class="round-tabs-container mb-4">
                        <div class="round-tabs">
                            @foreach($availableRounds as $roundNumber)
                            <a href="{{ route('users.tree', ['username' => $user->username, 'round' => $roundNumber]) }}"
                                class="round-tab {{ $round == $roundNumber ? 'active' : '' }}">
                                <i class="fas fa-layer-group me-2"></i>
                                Round {{ $roundNumber }}
                                @if($roundNumber == 1)
                                <small class="d-block">Main Tree</small>
                                @else
                                <small class="d-block">Spillover</small>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Tree Structure -->
                    <div class="tree-container">
                        <!-- Root Node: Selected User -->
                        <div class="tree-level">
                            <div class="user-box owner">
                                {{ $tree['owner']->username }}
                                @if($round > 1)
                                <br><small>Round {{ $round }}</small>
                                @endif
                            </div>
                        </div>

                        @if($tree['member_count'] == 0)
                        <!-- No downline members message -->
                        <div class="tree-level">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>{{ $tree['owner']->username }}</strong> has no downline members yet.
                                <br>
                                <small>When {{ $tree['owner']->username }} invites new members, they will appear here.</small>
                            </div>

                            <!-- Show user's position in main tree -->
                            @php
                            $userTreeEntry = \App\Models\ReferralRelationship::where('user_id', $user->id)->first();
                            @endphp
                            @if($userTreeEntry && $userTreeEntry->tree_owner_id != $user->id)
                            <div class="alert alert-secondary text-center mt-3">
                                <i class="fas fa-sitemap me-2"></i>
                                <strong>{{ $user->username }}</strong> is a member in <strong>{{ $userTreeEntry->tree_owner_username }}</strong>'s tree.
                                <br>
                                <small>Position: {{ $userTreeEntry->position }} under {{ $userTreeEntry->upline_username }}</small>
                                <br>
                                <a href="/users/{{ $userTreeEntry->tree_owner_username }}/tree" class="btn btn-sm btn-outline-primary mt-2">
                                    View {{ $userTreeEntry->tree_owner_username }}'s Tree
                                </a>
                            </div>
                            @endif
                        </div>
                        @else

                        <!-- Level 1: 2 Users -->
                        <div class="tree-level">
                            <div class="tree-level-title">Level 1 (2 Users)</div>
                            <div class="level-1-container">
                                @for($i = 0; $i < 2; $i++)
                                    @if(isset($tree['level1'][$i]) && $tree['level1'][$i])
                                    <div class="user-box {{ $tree['level1'][$i]->is_spillover ? 'spillover' : 'clickable' }}"
                                    @if(!$tree['level1'][$i]->is_spillover) onclick="viewUserTree('{{ $tree['level1'][$i]->user->username }}')" @endif>
                                    {{ $tree['level1'][$i]->user->username }} - {{ $tree['level1'][$i]->user->id }}
                                    @if($tree['level1'][$i]->is_spillover)
                                    <br><small>Spillover</small>
                                    @endif
                            </div>
                            @else
                            <div class="user-box empty">Empty</div>
                            @endif
                            @endfor
                        </div>
                    </div>

                    <!-- Level 2: 4 Users -->
                    <div class="tree-level">
                        <div class="tree-level-title">Level 2 (4 Users)</div>
                        <div class="level-2-container">
                            @for($i = 0; $i < 4; $i++)
                                @if(isset($tree['level2'][$i]) && $tree['level2'][$i])
                                <div class="user-box {{ $tree['level2'][$i]->is_spillover ? 'spillover' : 'clickable' }}"
                                @if(!$tree['level2'][$i]->is_spillover) onclick="viewUserTree('{{ $tree['level2'][$i]->user->username }}')" @endif>
                                {{ $tree['level2'][$i]->user->username }} - {{ $tree['level2'][$i]->user->id }}
                                @if($tree['level2'][$i]->is_spillover)
                                <br><small>Spillover</small>
                                @endif
                        </div>
                        @else
                        <div class="user-box empty">Empty</div>
                        @endif
                        @endfor
                    </div>
                </div>

                <!-- Level 3: 8 Users -->
                <div class="tree-level">
                    <div class="tree-level-title">Level 3 (8 Users)</div>
                    <div class="level-3-container">
                        @for($i = 0; $i < 8; $i++)
                            @if(isset($tree['level3'][$i]) && $tree['level3'][$i])
                            <div class="user-box {{ $tree['level3'][$i]->is_spillover ? 'spillover' : 'clickable' }}"
                            @if(!$tree['level3'][$i]->is_spillover) onclick="viewUserTree('{{ $tree['level3'][$i]->user->username }}')" @endif>
                            {{ $tree['level3'][$i]->user->username }} - {{ $tree['level3'][$i]->user->id }}
                            @if($tree['level3'][$i]->is_spillover)
                            <br><small>Spillover</small>
                            @endif
                    </div>
                    @else
                    <div class="user-box empty">Empty</div>
                    @endif
                    @endfor
                </div>
            </div>

            <!-- Level 4: 16 Users -->
            <div class="tree-level">
                <div class="tree-level-title">Level 4 (16 Users) - Final Level</div>
                <div class="level-4-container">
                    @for($i = 0; $i < 16; $i++)
                        @if(isset($tree['level4'][$i]) && $tree['level4'][$i])
                        <div class="user-box {{ $tree['level4'][$i]->is_spillover ? 'spillover' : 'clickable' }}"
                        @if(!$tree['level4'][$i]->is_spillover) onclick="viewUserTree('{{ $tree['level4'][$i]->user->username }}')" @endif>
                        {{ $tree['level4'][$i]->user->username }} - {{ $tree['level4'][$i]->user->id }}
                        @if($tree['level4'][$i]->is_spillover)
                        <br><small>Spillover</small>
                        @endif
                </div>
                @else
                <div class="user-box empty">Empty</div>
                @endif
                @endfor
            </div>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Only 4 levels are counted (2+4+8+16=30). Level 4 members can have their own trees. Click on them to view their separate tree structure.
            </div>
        </div>
        @endif



        <!-- Tree Status -->
        <div class="mt-4 p-3 bg-light rounded">
            <div class="row text-center">
                <div class="col-md-3">
                    <h5>Round {{ $round }} Members</h5>
                    <h3 class="text-primary">{{ $tree['member_count'] }}</h3>
                </div>
                <div class="col-md-3">
                    <h5>Round Status</h5>
                    <h3 class="{{ $tree['member_count'] >= 30 ? 'text-warning' : 'text-success' }}">
                        {{ $tree['member_count'] >= 30 ? 'Full' : 'Active' }}
                    </h3>
                </div>
                <div class="col-md-3">
                    <h5>Available Slots</h5>
                    <h3 class="text-info">{{ 30 - $tree['member_count'] > 0 ? 30 - $tree['member_count'] : 0 }}</h3>
                </div>
                <div class="col-md-3">
                    <h5>Total Rounds</h5>
                    <h3 class="text-secondary">{{ count($availableRounds) }}</h3>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUserTree(username) {
            // Navigate to the selected user's tree view
            window.open(`/users/${username}/tree`, '_blank');
            // window.location.href = `/users/${username}/tree`;
        }

        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Add tooltip functionality for user boxes
            const userBoxes = document.querySelectorAll('.user-box.clickable');
            userBoxes.forEach(box => {
                box.title = `Click to view ${box.textContent.trim()}'s tree`;
            });
        });
    </script>
</body>

</html>