<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gantt Chart - Creative Agency Hub</title>

    <link href="../../../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../../../public/assets/css/sb-admin-2.css" rel="stylesheet">`n    <link href="../../../public/assets/css/agency-theme.css" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include __DIR__ . '/../../../components/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include __DIR__ . '/../../../components/navbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800 font-weight-bold">Tiến Độ Dự Án (Gantt Chart)</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Biểu đồ tổng quan</h6>
                        </div>
                        <div class="card-body">
                            <div id="chart_div" style="width: 100%; height: 500px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include __DIR__ . '/../../../components/footer.php'; ?>
        </div>
    </div>

    <script src="../../../public/assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/sb-admin-2.min.js"></script>

    <script>
        google.charts.load('current', {'packages':['gantt']});
        google.charts.setOnLoadCallback(drawChart);

        async function drawChart() {
            try {
                const response = await fetch('/creative-agency-hub/public/api/tasks');
                const json = await response.json();
                
                if (json.status === 'success' && json.data.length > 0) {
                    var data = new google.visualization.DataTable();
                    data.addColumn('string', 'Task ID');
                    data.addColumn('string', 'Task Name');
                    data.addColumn('string', 'Resource');
                    data.addColumn('date', 'Start Date');
                    data.addColumn('date', 'End Date');
                    data.addColumn('number', 'Duration');
                    data.addColumn('number', 'Percent Complete');
                    data.addColumn('string', 'Dependencies');

                    json.data.forEach(task => {
                        let percent = 0;
                        if (task.status === 'Done') percent = 100;
                        else if (task.status === 'Review') percent = 80;
                        else if (task.status === 'Doing') percent = 50;

                        let endDate = new Date(task.deadline);
                        let startDate = new Date(endDate);
                        startDate.setDate(endDate.getDate() - 2);

                        data.addRow([
                            task.id.toString(),
                            task.title,
                            task.status,
                            startDate,
                            endDate,
                            null,
                            percent,
                            null
                        ]);
                    });

                    var options = {
                        height: 500,
                        gantt: {
                            trackHeight: 30
                        }
                    };

                    var chart = new google.visualization.Gantt(document.getElementById('chart_div'));
                    chart.draw(data, options);
                } else {
                    document.getElementById('chart_div').innerHTML = "<p class='text-center mt-5 text-muted'>Chưa có dữ liệu công việc để vẽ biểu đồ.</p>";
                }
            } catch(e) {
                console.error("Lỗi tải dữ liệu Gantt:", e);
            }
        }
    </script>
</body>
</html>
