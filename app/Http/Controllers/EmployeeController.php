<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller{
    /**
     * Display a listing of the resource.
     */

    private $rules = [
        'first_name' => 'required|max:20',
        'last_name' => 'required|max:40',
        'email' => 'required|max:255',
        'phone' => 'required|max:15',
        'job_position' => 'required|max:50',
        'date_hired' => 'required|date|before_or_equal:today',
        'avatar' => 'required|file|mimes:jpg,gif,png',
    ];

    public function index(){
        // get all employees
        $result = array('data' => array());
        $employees = Employee::all();

        // if no employees found
        if ($employees->count() <= 0){
            echo json_encode($result);
            return;
        }

        // found employees
        $index = 0;
        $today = new DateTime( date('Y-m-d') );
        foreach ($employees as $employee){
            $index++;
            $photo = '<img src="storage/images/' . $employee->avatar . '" width="60" height="60" class="img-thumbnail rounded-circle" style="aspect-ratio:1/1;object-fit: cover;">';
            
            // buttons for actions
            $buttons = '                
            <td>
            <a href="#" id="' . $employee->id . '" class="text-success mx-1 editIcon" data-bs-toggle="modal" data-bs-target="#editEmployeeModal"><i class="bi-pencil-square h4"></i></a>

            <a href="#" id="' . $employee->id . '" class="text-danger mx-1 deleteIcon"><i class="bi-trash h4"></i></a>
          </td>';

          
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



        echo json_encode($result);
        return ;
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(){
        //
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        // validator
        $validator = Validator::make($request->all(), $this->rules);

        // stop validating as soon as we found a validation error 
        if ($validator->stopOnFirstFailure()->fails()) {
            return json_encode($validator->validated()['errors']);
            
        }
        

        $file = $request->file('avatar');
        $extension = strtolower($file->extension()) ;


		$fileName = time() . '.' . $extension;

		$file->storeAs('public/images', $fileName);

		$employeeData = ['first_name' => $request->first_name, 'last_name' => $request->last_name, 'email' => $request->email, 'phone' => $request->phone, 'job_position' => $request->job_position,'date_hired' => $request->date_hired, 'avatar' => $fileName];
		
        try {
            
            Employee::create($employeeData);
            return response()->json([
                'status' => 200,
                'message' => 'Employee added successfully'
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => 400,
                'message' => $th->getMessage()
            ]);
        }


		
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee){
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        //
    }
}
