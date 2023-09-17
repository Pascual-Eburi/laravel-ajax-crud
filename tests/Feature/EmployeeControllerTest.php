<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Employee;
use Illuminate\Foundation\Testing\WithFaker;

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
}
 
