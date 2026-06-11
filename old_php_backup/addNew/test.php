<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realtime SSIN Form</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-dark text-white">

<div class="container mt-5">
    <form id="ssinForm" class="p-4 bg-secondary shadow-lg rounded-lg">
        <div class="card-body">
            <div class="form-group">
                <label for="ssinInput" class="font-semibold">SSIN Number</label>
                <input type="number" name="ssin" class="form-control bg-dark text-white" id="ssinInput" placeholder="Enter 12-digit SSIN" maxlength="12" required>
            </div>
            <button type="button" class="btn btn-info" id="checkSSIN">Check</button>

            <div id="existingData" class="mt-3" style="display: none;">
                <div class="card p-3 bg-dark text-white">
                    <h5 class="text-center">SSIN Details</h5>
                    <p><strong>Name:</strong> <span id="beneficiaryName"></span></p>
                    <p><strong>Date of Attaining 60:</strong> <span id="dateOf60"></span></p>
                    <p><strong>Phone:</strong> <span id="phoneNo"></span></p>
                    <p><strong>Status:</strong> <span id="status"></span></p>
                    <p><strong>Last Update:</strong> <span id="lastUpdate"></span></p>
                </div>
            </div>

            <div id="newData" class="mt-3" style="display: none;">
                <div class="form-group">
                    <label for="nameInput">Name</label>
                    <input type="text" name="name" class="form-control text-uppercase bg-dark text-white" id="nameInput" placeholder="Enter Name" required>
                </div>
                <div class="form-group">
                    <label for="dateInput">Date of Attaining 60</label>
                    <div class="input-group">
                        <input type="text" name="date" class="form-control bg-dark text-white" id="dateInput" readonly required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="generateDate">Generate</button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="phoneInput">Phone Number</label>
                    <div class="input-group">
                        <input type="text" name="phone" class="form-control bg-dark text-white" id="phoneInput" readonly required>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-secondary" id="generatePhone">Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-center">
            <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;">Submit</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    $('#checkSSIN').click(function() {
        var ssin = $('#ssinInput').val();
        console.log("Checking SSIN:", ssin); // Debugging log

        if (ssin.length !== 12) {
            Swal.fire('Error', 'SSIN must be 12 digits', 'error');
            return;
        }

        $.ajax({
            url: 'check_ssin.php',
            method: 'POST',
            data: { ssin: ssin },
            dataType: 'json',
            success: function(response) {
                console.log("Response received:", response); // Debugging log

                if (response.exists) {
                    $('#beneficiaryName').text(response.name);
                    $('#dateOf60').text(response.date_of_attaining_60);
                    $('#phoneNo').text(response.phone_no);
                    $('#status').text(response.status);
                    $('#lastUpdate').text(response.last_update);
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

</body>
</html>
