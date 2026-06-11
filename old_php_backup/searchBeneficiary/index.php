<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /");
    exit();
}

$user_name = $_SESSION["user_name"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BMSSY SERVICE | KHARUI - 1</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="/plugins/fontawesome-free/css/all.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="/dist/css/adminlte.min.css">
  <script src="https://kit.fontawesome.com/2b88caa317.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
/* Gradient Button Styling */
.btn-gradient {
    background: linear-gradient(45deg, #6a11cb, #2575fc);
    border: none;
    color: white;
    padding: 8px 12px;
    font-size: 14px;
    border-radius: 50px;
    transition: 0.3s ease-in-out;
}

.btn-gradient:hover {
    background: linear-gradient(45deg, #2575fc, #6a11cb);
    transform: scale(1.1);
}
</style>
</head>
<body class="hold-transition dark-mode sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">

  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__wobble" src="/dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
  </div>

    <?php include('../inc/nav.php'); ?>
    <?php include('../inc/sideber.php'); ?>

    

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Search PF Data</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Dashboard v2</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        
        <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Search PF<small> Data</small></h3>
              </div>
              <!-- /.card-header -->
              <!-- form start -->
              <form id="ssinForm">
                <div class="card-body">
                  <div class="form-group">
                    <label for="exampleInputEmail1">SSIN</label>
                    <input type="number" name="ssin" class="form-control" id="ssinInput" placeholder="Enter 12-digit SSIN" maxlength="12" required>
                  </div>
                <!-- /.card-body -->
                    <button type="button" class="btn btn-sm btn-gradient" id="checkSSIN">
                        <i class="fas fa-search"></i> Check SSIN
                    </button>
              </form>
            </div>
            <!-- /.card -->
<script>
document.getElementById("checkSSIN").addEventListener("click", function () {
  const ssin = document.getElementById("ssinInput").value.trim();

  // Check if SSIN is valid
  if (ssin.length !== 12) {
    alert("Please enter a valid 12-digit SSIN.");
    return;
  }

  fetch("check_ssin.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "ssin=" + encodeURIComponent(ssin)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Populate profile section
      document.querySelector(".profile-username").textContent = data.name;
      document.querySelector(".text-muted.text-center").innerHTML = `
        <span class="badge badge-success px-2 py-1" style="font-size: 0.85rem;">${data.status}</span><br>
        <span class="badge badge-secondary px-2 py-1 mt-1" style="font-size: 0.75rem;">Last Updated: ${formatDateTime(data.last_update)}</span>
      `;
      document.querySelector(".list-group-item:nth-child(1) .float-right").textContent = ssin;
      document.querySelector(".list-group-item:nth-child(2) .float-right").textContent = data.date60;  
      document.querySelector(".list-group-item:nth-child(3) .float-right").textContent = data.phone;

      // Show hidden section
      document.querySelector(".ssin-profile").style.display = "block";
    } else {
      alert("SSIN not found.");
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Something went wrong.");
  });
});

// Date formatter function
function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  const options = {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  };

  const formatted = date.toLocaleString('en-US', options).toUpperCase().replace(',', '');
  return formatted;
}
</script>





      </div><!--/. container-fluid -->
    </section>
    <!-- /.content -->

      <!-- Main content -->
    <section class="content ssin-profile" style="display: none;">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle"
                       src="../../dist/img/user4-128x128.jpg"
                       alt="User profile picture">
                </div>

                <h3 class="profile-username text-center">USER NAME</h3>

                <p class="text-muted text-center">REMARK</p>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>SSIN</b> <a class="float-right">142070012345</a>
                  </li>
                  <li class="list-group-item">
                    <b>DATE OF 60 AGE</b> <a class="float-right">21 JAN 2036</a>
                  </li>
                  <li class="list-group-item">
                    <b>PHONE NO</b> <a class="float-right">9999999999</a>
                  </li>
                </ul>

                <a href="#" class="btn btn-primary btn-block"><i class="fa-regular fa-address-card"></i> <b>Update Profile</b></a>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#timeline" data-toggle="tab">Timeline</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="timeline">
                    <!-- The timeline -->
                    <div class="timeline timeline-inverse">
                      <!-- timeline time label -->
                      <div class="time-label">
                        <span class="bg-danger">
                          10 Feb. 2014
                        </span>
                      </div>
                      <!-- /.timeline-label -->
                      <!-- timeline item -->
                      <div>
                        <i class="fas fa-envelope bg-primary"></i>

                        <div class="timeline-item">
                          <span class="time"><i class="far fa-clock"></i> 12:05 PM</span>

                          <h3 class="timeline-header"><a href="#">PF UPDATE</a></h3>

                          <div class="timeline-body">
                            <p>
                              <span class="bg-success text-white px-2 py-1 rounded">12 JAN 2020</span>
                              TO
                              <span class="bg-success text-white px-2 py-1 rounded">12 DECEMBER 2024</span>
                              (NO OF MONTH) - RS 100
                            </p>
                          </div>
                        </div>
                      </div>
                      <!-- END timeline item -->
                      <div>
                        <i class="far fa-clock bg-gray"></i>
                      </div>
                    </div>
                  </div>
                  <!-- /.tab-pane -->

                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <?php include('../inc/footer.php'); ?>
</div>
<!-- ./wrapper -->
<script>
$(document).ready(function() {
    $('#checkSSIN').click(function() {
        var ssin = $('#ssinInput').val();
        console.log("Checking SSIN:", ssin); // Debugging log

        if (ssin.length !== 12) {
            Swal.fire('Error', 'SSIN must be 12 digits', 'error');
            return;
        }
        function formatDateTime(dateString) {
    const date = new Date(dateString);
    const options = { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
    return date.toLocaleString('en-GB', options).replace(',', '').replace(':', '.');
}

        $.ajax({
            url: 'check_ssin.php',
            method: 'POST',
            data: { ssin: ssin },
            dataType: 'json',
            success: function(response) {
                console.log("Response received:", response); // Debugging log

                if (response.exists) {
                    $('#approvedSSIN').text(response.ssin);
                    $('#beneficiaryName').text(response.name);
                    $('#dateOf60').text(response.date_of_attaining_60);
                    $('#phoneNo').text(response.phone_no);
                    $('#status').text(response.status);
                    // Format last update before setting
                    $('#lastUpdate').text(formatDateTime(response.last_update));
                    $('#existingData').show();
                    $('#newData').hide();
                    $('#submitBtn').hide();
                } else {
                    $('#ssinInput').prop('readonly', true);
                    $('#checkSSIN').hide();
                    $('#existingData').hide();
                    $('#newData').show();
                    $('#submitBtn').show();
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX Error:", error);
                Swal.fire('Error', 'Failed to check SSIN. Check console for details.', 'error');
            }
        });
    });

    $('#generateDate').click(function() {
        var start = new Date(2043, 0, 1);
        var end = new Date(2052, 0, 1);
        var randomDate = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
        $('#dateInput').val(randomDate.toISOString().split('T')[0]);
    });

    $('#generatePhone').click(function() {
        var phone = '9' + Math.floor(100000000 + Math.random() * 900000000);
        $('#phoneInput').val(phone);
    });

    $('#ssinForm').submit(function(e) {
        e.preventDefault();
        console.log("Submitting form...");

        $.ajax({
            url: 'submit_ssin.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log("Form submission response:", response);

                if (response.success) {
                    Swal.fire('Success', response.success, 'success');
                    $('#ssinForm')[0].reset();
                    $('#newData').hide();
                    $('#checkSSIN').show();
                    $('#ssinInput').prop('readonly', false);
                } else {
                    Swal.fire('Error', response.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.log("AJAX Submission Error:", error);
                Swal.fire('Error', 'Form submission failed. Check console.', 'error');
            }
        });
    });
});
</script>
<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="/dist/js/adminlte.js"></script>

<!-- PAGE PLUGINS -->
<!-- jQuery Mapael -->
<script src="/plugins/jquery-mousewheel/jquery.mousewheel.js"></script>
<script src="/plugins/raphael/raphael.min.js"></script>
<script src="/plugins/jquery-mapael/jquery.mapael.min.js"></script>
<script src="/plugins/jquery-mapael/maps/usa_states.min.js"></script>
<!-- ChartJS -->
<script src="/plugins/chart.js/Chart.min.js"></script>

<!-- AdminLTE for demo purposes -->
<script src="/dist/js/demo.js"></script>
<!-- AdminLTE dashboard demo (This is only for demo purposes) -->
<script src="/dist/js/pages/dashboard2.js"></script>
</body>
</html>
