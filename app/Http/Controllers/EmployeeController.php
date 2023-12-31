<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller{
    /**
     * Display a listing of the resource.
     **/

    private array $rules = [
        'first_name' => 'required|max:20',
        'last_name' => 'required|max:40',
        'email' => 'required|max:255',
        'phone' => 'required|max:15',
        'job_position' => 'required|max:50',
        'date_hired' => 'required|date|before_or_equal:today',
        'avatar' => 'required|file|mimes:jpg,gif,png'

    ];


    /**
     * Handle employee list ajax request
     * @throws \Exception
     * @returns string
     */

    public function index():string{
        // get all employees
        $result = array('data' => array());
        $employees = Employee::all();

        // if no employees found
        if ($employees->count() <= 0){
            return  json_encode($result);

        }

        // found employees
        $index = 0;
        $today = new DateTime( date('Y-m-d') );
        foreach ($employees as $employee){
            $index++;
            # storage/images/'
            $photo = '<img src="storage/avatars/'. $employee->avatar.'" width="60" height="60" class="img-thumbnail rounded-circle" style="aspect-ratio:1/1;object-fit: cover;">';

            // buttons for actions
            // data-bs-toggle="modal" data-bs-target="#editEmployeeModal"
            $buttons = "<td>
            <a href=\"#\" data-employee-id=\"" . $employee->id . "\" class=\"text-success mx-1 editIcon\" ><i class=\"bi-pencil-square h4\"></i></a>

            <a href=\"#\" data-employee-id=\"" . $employee->id . "\" class=\"text-danger mx-1 deleteIcon\"><i class=\"bi-trash h4\"></i></a>
          </td>";


          $hired_date = new DateTime($employee->date_hired);
          $seniority = $today->diff($hired_date)->days;

          $result['data'][] = array(
            $index,
            $photo,
            $employee->first_name . ' '. $employee->last_name,
            $employee->email,
            $employee->phone,
            $employee->job_position,
            $employee->date_hired,
            $seniority , // experience
            $buttons
          );
        }

        return json_encode($result);

    }


    /**
     * Handle a create employee ajax request
     * @param Request $request
     * @return string|JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): string |JsonResponse
    {
        // validator

        $validator = Validator::make($request->all(), $this->rules);

        // stop validating as soon as we found a validation error
        if ($validator->stopOnFirstFailure()->fails()) {
            return json_encode($validator->validated()['errors']);

        }

        $file_name = $request->file('avatar')->hashName();

        # $file->storeAs('public/images', $fileName);
        $request->file('avatar')->storeAs(
            'public/avatars', $file_name
        );

        //$request->file('avatar')->store('avatars');
		$employeeData = ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'email' => $request->email, 'phone' => $request->phone, 'job_position' => $request->job_position,'date_hired' => $request->date_hired, 'avatar' => $file_name];

        try {

            Employee::create($employeeData);
            return response()->json([
                'message' => 'Employee added successfully',
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }



    }



    /**
     * Handle get single employee ajax request.
     * @param Request $request;
     * @returns JsonResponse;
     */
    public function show(Request $request): JsonResponse
    {
        // find employee
        try {
            $id = $request->id;
            $employee = Employee::find($id);
            if ($employee){
                return response()->json($employee);
            }

            return response()->json(['message' => "Employee not found"],404);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }


    }

    /**
     * Handle update employee ajax request.
     * @param Request $request;
     * @return JsonResponse|string;
     *@throws ValidationException
     */
    public function update(Request $request): JsonResponse|string
    {

        $file_name = '';
        // find employee
        try {
            $employee = Employee::find($request->emp_id);
            if(!$employee){
                return response()->json([
                'message' => "Employee not found"
                ], 404);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }


        if (!$request->hasFile('avatar')){
            $this->rules['avatar'] = '';
            $file_name = $request->emp_avatar;
        }

        // validate employee data
        $validator = Validator::make($request->all(), $this->rules);

        // stop validating as soon as we found a validation error
        if ($validator->stopOnFirstFailure()->fails()) {
            return json_encode($validator->validated()['errors']);
        }

        // so on, everything ok,

        if ( $request->hasFile('avatar') ){

/*             $file_name = $request->file('avatar')->store('avatars');
            $file_name = $request->photo->store('images', 'public'); */

            $file_name = $request->file('avatar')->hashName();

            # $file->storeAs('public/images', $fileName);
            $request->file('avatar')->storeAs(
                'public/avatars', $file_name
            );
            // delete old avatar in the storage
            if ( $employee->avatar ){
                Storage::delete('public/avatars/'. $employee->avatar);
            }

        }


        $employee_data = ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'email' => $request->email, 'phone' => $request->phone, 'job_position' => $request->job_position,'date_hired' => $request->date_hired, 'avatar' => $file_name
        ];

        // update employee data
        try {

            $employee->update($employee_data);
            return response()->json([
                'message' => 'Employee updated successfully successfully'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }



    }

    /**
     * Handle delete employee ajax request.
     * @param Request $request;
     * @returns JsonResponse
     */
    public function destroy(Request $request):JsonResponse{
        //
        try {
            $employee = Employee::find($request->id);
            if(!$employee){
                return response()->json([
                    'message' => 'Employee Not Found',
                ], 404);
            }

            # 'public/images/' .
            if (Storage::delete( 'public/avatars/' . $employee->avatar)){
                Employee::destroy($request->id);
                return response()->json([
                    'message' => 'Employee Deleted Successfully',
                ], 200);
            }

            return response()->json([
                'message' => "Unable to delete employee avatar",
            ], 500);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }

    }
}
