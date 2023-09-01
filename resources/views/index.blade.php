<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRUD App Laravel & Ajax</title>
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.2/css/bootstrap.min.css' />
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.5.0/font/bootstrap-icons.min.css' />
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/dt-1.10.25/datatables.min.css" />

</head>
{{-- add new employee modal start --}}
<x-add-employee-modal />
{{-- add new employee modal end --}}

{{-- edit employee modal start --}}
<x-edit-employee-modal />
{{-- edit employee modal end --}}

<body class="bg-light">
  <div class="container">
    <div class="row my-5">
      <div class="col-lg-12">
        <div class="card shadow">
          <div class="card-header d-flex justify-content-between align-items-center bg-white">
            <h6 class="text-muted">Manage Employees</h6>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal"><i class="bi-plus-circle me-2"></i>Add New Employee</button>
          </div>
          <div class="card-body table-responsive" >
            <table class="table table-striped w-100 align-middle" id="employeesTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Avatar</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Job</th>
                  <th>Hired</th>
                  <th>Experience</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.0.2/js/bootstrap.bundle.min.js'></script>
  <script type="text/javascript" src="https://cdn.datatables.net/v/bs5/dt-1.10.25/datatables.min.js"></script>
  <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    $(function() {

      // Initialize dataTable
      const employeesTable = 	$('#employeesTable').DataTable({
        'ajax': '{{ route("list") }}',
        'method': 'GET',
        'order': []
      });
      

      // add new employee ajax request
      $("#add_employee_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        
        $.ajax({
          url: '{{ route("store") }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          beforeSend: function(){
            $("#add_employee_btn").text('Adding...');
          },
          success: function(response) {
            console.log(response)
            if (response.status === 200) {
              employeesTable.ajax.reload(null, false);

              $("#add_employee_form")[0].reset();
              $("#addEmployeeModal").modal('hide'); 
              Swal.fire( 'Added!', response.message, 'success' )
              
            }
            
            
          },
          error: function(response){
            //console.log(Object.keys(response.responseJSON.errors)[0], response.responseJSON)
              // check form validation errors
              if (response.status === 422){
                const error = response.responseJSON.message || 'Error while validatig your data';
                const title = response.statusText || 'Something went wrong';
                Swal.fire(title, error,'error' );

                return
              }

              Swal.fire('Something went wrong', 'Error while trying to store the data','error' );


          },
          complete: function(){
            $("#add_employee_btn").text('Add Employee');
            return
          }
        });

        return
      });

      // edit employee ajax request
      $(document).on('click', '.editIcon', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        $.ajax({
          url: '{{ route("edit") }}',
          method: 'get',
          data: {
            id: id,
            _token: '{{ csrf_token() }}'
          },
          success: function(response) {
            $("#first_name").val(response.first_name);
            $("#last_name").val(response.last_name);
            $("#email").val(response.email);
            $("#phone").val(response.phone);
            $("#post").val(response.post);
            $("#avatar").html(
              `<img src="storage/images/${response.avatar}" width="100" class="img-fluid img-thumbnail">`);
            $("#emp_id").val(response.id);
            $("#emp_avatar").val(response.avatar);
          }
        });
      });

      // update employee ajax request
      $("#edit_employee_form").submit(function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        $("#edit_employee_btn").text('Updating...');
        $.ajax({
          url: '{{ route("update") }}',
          method: 'post',
          data: fd,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function(response) {
            if (response.status == 200) {
              Swal.fire(
                'Updated!',
                'Employee Updated Successfully!',
                'success'
              )
              fetchAllEmployees();
            }
            $("#edit_employee_btn").text('Update Employee');
            $("#edit_employee_form")[0].reset();
            $("#editEmployeeModal").modal('hide');
          }
        });
      });

      // delete employee ajax request
      $(document).on('click', '.deleteIcon', function(e) {
        e.preventDefault();
        let id = $(this).attr('id');
        let csrf = '{{ csrf_token() }}';
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: '{{ route("delete") }}',
              method: 'delete',
              data: {
                id: id,
                _token: csrf
              },
              success: function(response) {
                console.log(response);
                Swal.fire(
                  'Deleted!',
                  'Your file has been deleted.',
                  'success'
                )
                fetchAllEmployees();
              }
            });
          }
        })
      });

      // fetch all employees ajax request
      fetchAllEmployees();

      function fetchAllEmployees() {
        $.ajax({
          url: '{{ route("list") }}',
          method: 'get',
          success: function(response) {
            console.log(response)
            /*             $("table").DataTable({
              $("#show_all_employees").html(response);
                          order: [0, 'desc']
                        }); */
          }, 
          error: function(e) {
            console.log(e)
          }
        });
      }
    });
  </script>
</body>

</html>