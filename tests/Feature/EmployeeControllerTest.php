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
        $response->assertStatus(200)
            ->assertJson([
                'status' => 201,
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
    public function test_it_handles_validation_errors()
    {
        // Crea un empleado de ejemplo en la base de datos
        $employee = factory(Employee::class)->create();

        // Genera datos simulados para la solicitud de actualización con errores de validación
        $invalidData = [
            'emp_id' => $employee->id,
            'first_name' => '', // Campo requerido en las reglas de validación
            // Agrega más campos inválidos aquí según tus reglas de validación
        ];

        // Realiza una solicitud POST JSON al endpoint de actualización de empleado con datos inválidos
        $response = $this->json('POST', '/update', $invalidData);

        // Verifica que la respuesta tenga un código de estado 200 (aunque los datos sean inválidos)
        $response->assertStatus(200);

        // Verifica que la respuesta contenga los errores de validación en formato JSON
        $response->assertJsonValidationErrors([
            'first_name', // Nombre del campo que falló la validación
            // Agrega más campos aquí según tus reglas de validación
        ]);
    }

    /**
     * @test
     */
    public function test_it_handles_employee_not_found()
    {
        // Proporciona un ID de empleado que no existe en la base de datos
        $nonExistingEmployeeId = 999;

        // Genera datos simulados para la solicitud de actualización
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

        // Realiza una solicitud POST JSON al endpoint de actualización de empleado con ID no válido
        $response = $this->json('POST', '/update', $newData);

        // Verifica que la respuesta tenga un código de estado 404 (not found)
        $response->assertStatus(404);

        // Verifica que la respuesta contenga un mensaje de error en formato JSON
        $response->assertJson([
            'message' => 'Employee not found',
        ]);
    }

    /**
     * @test
     */
    public function test_it_handles_internal_server_error(){
        // Simula un error interno al actualizar el empleado (puedes ajustar esta lógica según tu necesidad)
        $this->mock(Storage::class, function ($mock) {
            $mock->shouldReceive('storeAs')->andReturn(false);
        });

        // Crea un empleado de ejemplo en la base de datos
        $employee = factory(Employee::class)->create();

        // Genera datos simulados para la solicitud de actualización
        $newData = [
            'emp_id' => $employee->id,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => UploadedFile::fake()->image('new_avatar.jpg'),
        ];

        // Realiza una solicitud POST JSON al endpoint de actualización de empleado
        $response = $this->json('POST', '/update', $newData);

        // Verifica que la respuesta tenga un código de estado 500 (internal server error)
        $response->assertStatus(500);

        // Verifica que la respuesta contenga un mensaje de error en formato JSON
        $response->assertJson([
            'message' => 'Error Message Here', // Puedes ajustar este mensaje según tu lógica de error
        ]);
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

        Storage::disk('public')->assertMissing('images/' . $employee->avatar);
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


 
