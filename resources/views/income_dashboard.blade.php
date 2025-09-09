<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Dashboard - Heena MLM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .stat-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .income-section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        .income-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .income-table th,
        .income-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .income-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Income Dashboard</h1>
            <p>Track your MLM income distributions and earnings</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Income Received</div>
                <div class="stat-value" id="totalReceived">Loading...</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Total Distributions Made</div>
                <div class="stat-value" id="totalDistributed">Loading...</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Active Level Plans</div>
                <div class="stat-value" id="activePlans">Loading...</div>
            </div>
        </div>

        <div class="income-section">
            <h2 class="section-title">📊 Income Received</h2>
            <div id="incomeReceived">
                <p>Loading income data...</p>
            </div>
        </div>

        <div class="income-section">
            <h2 class="section-title">📤 Income Distributions</h2>
            <div id="incomeDistributions">
                <p>Loading distribution data...</p>
            </div>
        </div>

        <div class="income-section">
            <h2 class="section-title">🛒 Process Level Plan Purchase</h2>
            <form id="purchaseForm">
                <div style="margin-bottom: 15px;">
                    <label for="level_plan_id">Select Level Plan:</label>
                    <select id="level_plan_id" name="level_plan_id" required style="padding: 8px; width: 200px; margin-left: 10px;">
                        <option value="">Select a plan...</option>
                        @foreach(\App\Models\LevelPlan::active()->ordered()->get() as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->price }} TRX</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Process Purchase & Distribute Income</button>
            </form>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/dashboard" class="btn">← Back to Dashboard</a>
            <a href="/buy-slot-form" class="btn">Buy New Slot</a>
        </div>
    </div>

    <script>
        // Load income data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadIncomeData();
        });

        function loadIncomeData() {
            // Load income received
            fetch('/income/received')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayIncomeReceived(data.data);
                    }
                })
                .catch(error => console.error('Error loading income received:', error));

            // Load income distributions
            fetch('/income/distribution')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayIncomeDistributions(data.data);
                    }
                })
                .catch(error => console.error('Error loading income distributions:', error));
        }

        function displayIncomeReceived(data) {
            const container = document.getElementById('incomeReceived');
            const totalReceived = data.total_received || 0;
            
            document.getElementById('totalReceived').textContent = totalReceived.toFixed(2) + ' TRX';

            if (data.distributions && data.distributions.length > 0) {
                let html = '<table class="income-table"><thead><tr><th>From User</th><th>Level</th><th>Percentage</th><th>Amount</th><th>Date</th></tr></thead><tbody>';
                
                data.distributions.forEach(dist => {
                    html += `<tr>
                        <td>${dist.from_user}</td>
                        <td>${dist.level}</td>
                        <td>${dist.percentage}%</td>
                        <td>${dist.amount} TRX</td>
                        <td>${new Date(dist.date).toLocaleDateString()}</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p>No income received yet.</p>';
            }
        }

        function displayIncomeDistributions(data) {
            const container = document.getElementById('incomeDistributions');
            const totalDistributed = data.total_distributed || 0;
            
            document.getElementById('totalDistributed').textContent = totalDistributed.toFixed(2) + ' TRX';

            if (data.distributions && data.distributions.length > 0) {
                let html = '<table class="income-table"><thead><tr><th>Level</th><th>Recipient</th><th>Percentage</th><th>Amount</th><th>Description</th></tr></thead><tbody>';
                
                data.distributions.forEach(dist => {
                    html += `<tr>
                        <td>${dist.level}</td>
                        <td>${dist.recipient}</td>
                        <td>${dist.percentage}%</td>
                        <td>${dist.amount} TRX</td>
                        <td>${dist.description}</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p>No distributions made yet.</p>';
            }
        }

        // Handle purchase form submission
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/income/process-purchase', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Purchase processed successfully! Income has been distributed.');
                    loadIncomeData(); // Reload data
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing the purchase.');
            });
        });
    </script>
</body>
</html>
