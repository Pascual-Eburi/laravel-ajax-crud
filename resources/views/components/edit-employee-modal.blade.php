{{-- edit employee modal start --}}
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="exampleModalLabel" data-bs-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Edit Employee</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="#" method="POST" id="edit_employee_form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="emp_id" id="emp_id">
        <input type="hidden" name="emp_avatar" id="emp_avatar">
        <div class="modal-body p-4 bg-light">
        <div class="row">
            <div class="col-md-6">
              <label for="first_name">First Name</label>
              <input type="text" name="first_name" class="form-control" placeholder="First Name">
            </div>
            <div class="col-md-6">
              <label for="last_name">Last Name</label>
              <input type="text" name="last_name" class="form-control" placeholder="Last Name">
            </div>
            <div class="my-2 col-12">
              <label for="email">E-mail</label>
              <input type="email" name="email" class="form-control" placeholder="E-mail">
            </div>
            <div class="col-md-6">
              <label for="phone">Phone</label>
              <input type="tel" name="phone" class="form-control" placeholder="Phone">
            </div>
            <div class="col-md-6">
              <label for="job_position">Job Position</label>
              <input type="text" name="job_position" class="form-control" placeholder="MERN developer...">
            </div>
            <div class="col-md-5 my-2">
              <label for="date_hired">Date hired</label>
              <input type="date" name="date_hired" class="form-control" >
            </div>
            <div class="col-md-7 my-2">
              <label for="avatar">Select Avatar</label>
              <input type="file" name="avatar" class="form-control">
            </div>
          </div>
          <div class="mt-2" id="avatar">

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" id="edit_employee_btn" class="btn btn-success">Update Employee</button>
        </div>
      </form>
    </div>
  </div>
</div>
{{-- edit employee modal end --}}