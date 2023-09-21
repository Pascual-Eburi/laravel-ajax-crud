<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Employee;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @test
     */
    public function test_it_can_store_an_employee(){
        Storage::fake('local'); 
        $file = UploadedFile::fake()->image('avatar.jpg');
        $file_name = $file->hashName();

        $data = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => $file,
        ];

        

        $response = $this->json('POST', '/store', $data);
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Employee added successfully',
            ]);


        $this->assertDatabaseHas('employees', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'job_position' => $data['job_position'],
            'date_hired' => $data['date_hired'],
            'avatar' => 'avatars/' . $file_name
        ]);

        $employee = Employee::first();
        $this->assertNotNull($employee->avatar);
        Storage::disk('local')->assertExists($employee->avatar);
        $this->assertFileEquals($file, Storage::disk('local')->path($employee->avatar) );


    } 

    public function test_it_can_not_store_employee_if_invalid_data(){
        $data = [
            'first_name' => '', // first_name its required
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->lastName, // is not an email
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => '',
        ];
        $response = $this->json('POST', '/store', $data);
        $response->assertStatus(422)
                    ->assertUnprocessable();
    }

    public function test_it_handles_internal_server_error_when_storing_employee(){
        $employee = Employee::factory()->create();
        $data = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $employee->email, // this must be a unique email
            'phone' => $employee->phone, // this must be a unique phone number
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ];
        $this->json('POST', "/store" , $data)
                    ->assertStatus(500)
                    ->assertJson( fn (AssertableJson $json) => $json->has('message') );
    }
    /**--------------------------------------------------------
     *  Show method tests
     *--------------------------------------*/
    public function test_it_can_show_an_employee(){
        $employee = Employee::factory()->create();
        $response = $this->json('GET', '/edit', ['id' => $employee->id]);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $employee->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone
        ]);
    }

    /**
     * @test
     */
    public function test_it_returns_error_for_invalid_employee_id_when_fetching_employee_data()
    {
        // Proporciona un ID de empleado que no existe en la base de datos
        $invalidEmployeeId = 999;

        // Realiza una solicitud GET al endpoint de mostrar empleado con el ID no válido
        $response = $this->json('GET', '/edit', ['id' => $invalidEmployeeId]);

        // Verifica que la respuesta contenga un código de estado 404 (not found) o 501
        $response->assertStatus(404) || $response->assertStatus(501);
        // Verifica que la respuesta contenga un mensaje de error en formato JSON
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->has('message')
        );
    }


    /**===============================================
     * Update method tests
     =====================================================*/
    public function test_it_can_update_an_employee_with_old_avatar(){
        $employee = Employee::factory()->create();
        $newData = [
            'emp_id' => $employee->id,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'emp_avatar' => $employee->avatar,
        ];

        $this->json('POST', '/update', $newData)
                ->assertStatus(200)
                ->assertJson(['message' => 'Employee updated successfully successfully']);
        $this->assertDatabaseHas('employees',[
            'id' => $employee->id,
            'avatar' => $employee->avatar
        ]);
    }
    
     public function test_it_can_update_an_employee_with_new_avatar() {
        $employee = Employee::factory()->create();
        Storage::fake('local');
        $file = UploadedFile::fake()->image('new_avatar.jpg');
        $file_name = $file->hashName();
        $newData = [
            'emp_id' => $employee->id,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => $file,
        ];

        $response = $this->json('POST', '/update', $newData);
        $response->assertStatus(200);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'email' => $newData['email'],
            'phone' => $newData['phone'],
            'avatar' => 'avatars/' . $file_name
        ]);

        Storage::disk('local')->assertExists('avatars/' .$newData['avatar']->hashName());
        Storage::disk('public')->assertMissing($employee->avatar);
    }

    /**
     * @test
     */
    public function test_it_handles_validation_errors_when_updating_employee(){
        $employee = Employee::factory()->create();

        $invalidData = [
            'emp_id' => $employee->id,
            'first_name' => '', // this field is required
        ];

        $response = $this->json('POST', '/update', $invalidData);
        $response->assertStatus(422)
                    ->assertUnprocessable();
    }

    /**
     * @test
     */
    public function test_it_handles_employee_not_found_when_updating_employee()
    {
        $nonExistingEmployeeId = 999;
        $newData = [
            'emp_id' => $nonExistingEmployeeId,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => UploadedFile::fake()->image('new_avatar.jpg'),
        ];

        $this->json('POST', '/update', $newData)
                ->assertStatus(404)
                ->assertJson(['message' => 'Employee not found',]);
    }

    /**
     * @test
     */
    public function test_it_handles_internal_server_error_when_updating_employee(){
        $employee = Employee::factory()->create();
        $data = [
            'emp_id' => $employee,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => $employee->avatar,
        ];
        $this->json('POST', "/update" , $data)
                    ->assertStatus(500)
                    ->assertJson( fn (AssertableJson $json) => $json->has('message') );
    }



        
    /**--------------------------------------------------------
     *  Delete method tests
     *--------------------------------------*/

    public function test_it_can_destroy_an_employee(){

        $employee = Employee::factory()->create();
        $this->json('DELETE', "/delete", ['id' => $employee->id])
                    ->assertStatus(200);

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);

        Storage::disk('public')->assertMissing('avatars/' . $employee->avatar);
    }

    /**
     * @test
     */
    public function it_returns_error_for_non_existing_employee_when_deleting_employee(){

        $nonExistingEmployeeId = 999;
        $this->json('DELETE', "/delete", ['id'=>$nonExistingEmployeeId])
                ->assertStatus(404)
                ->assertJson([
                    'message' => 'Employee Not Found',
                ]);
    }

    /**
     * @test
     */
    public function it_returns_error_for_internal_server_error_when_deleting_employee(){

        $employee = Employee::factory()->create();
        $this->json('DELETE', "/delete" , ['id' => $employee])
                    ->assertStatus(500)
                    ->assertJson( fn (AssertableJson $json) => $json->has('message') );
    }

}


 
