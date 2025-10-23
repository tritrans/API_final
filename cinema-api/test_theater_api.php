<?php
/**
 * Script test cho TheaterController API endpoints
 * Chạy: php test_theater_api.php
 */

require_once __DIR__ . '/vendor/autoload.php';

class TheaterApiTester
{
    private $baseUrl;
    private $token;
    private $adminToken;
    private $movieManagerToken;
    private $regularToken;

    public function __construct()
    {
        $this->baseUrl = 'http://localhost:8000/api'; // Thay đổi URL theo môi trường
        echo "🎬 Theater API Tester Started\n";
        echo "Base URL: {$this->baseUrl}\n\n";
    }

    public function runAllTests()
    {
        echo "=== THEATER API TESTING ===\n\n";
        
        $this->setupTokens();
        $this->testTheaterEndpoints();
        $this->testScheduleEndpoints();
        
        echo "\n=== TESTING COMPLETED ===\n";
    }

    private function setupTokens()
    {
        echo "🔐 Setting up authentication tokens...\n";
        
        // Tạo tokens cho các role khác nhau
        $this->adminToken = $this->getToken('admin@test.com', 'password');
        $this->movieManagerToken = $this->getToken('manager@test.com', 'password');
        $this->regularToken = $this->getToken('user@test.com', 'password');
        
        echo "✅ Tokens setup completed\n\n";
    }

    private function getToken($email, $password)
    {
        $data = [
            'email' => $email,
            'password' => $password
        ];

        $response = $this->makeRequest('POST', '/auth/login', $data);
        
        if ($response && isset($response['success']) && $response['success']) {
            return $response['data']['access_token'] ?? null;
        }
        
        return null;
    }

    private function testTheaterEndpoints()
    {
        echo "🏢 Testing Theater Endpoints...\n";
        
        // Test 1: Admin can view theater details
        $this->testViewTheater('admin', $this->adminToken);
        
        // Test 2: Movie Manager can view theater details
        $this->testViewTheater('movie_manager', $this->movieManagerToken);
        
        // Test 3: Regular user cannot view theater details
        $this->testViewTheater('regular_user', $this->regularToken, false);
        
        // Test 4: Unauthenticated user cannot view theater details
        $this->testViewTheater('unauthenticated', null, false);
        
        // Test 5: Admin can update theater
        $this->testUpdateTheater('admin', $this->adminToken);
        
        // Test 6: Movie Manager can update theater
        $this->testUpdateTheater('movie_manager', $this->movieManagerToken);
        
        // Test 7: Regular user cannot update theater
        $this->testUpdateTheater('regular_user', $this->regularToken, false);
        
        // Test 8: Admin can delete theater (without schedules)
        $this->testDeleteTheater('admin', $this->adminToken);
        
        // Test 9: Regular user cannot delete theater
        $this->testDeleteTheater('regular_user', $this.regularToken, false);
        
        echo "\n";
    }

    private function testViewTheater($userType, $token, $shouldSucceed = true)
    {
        echo "  📋 Testing view theater as {$userType}... ";
        
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        $response = $this->makeRequest('GET', '/admin/theaters/1', null, $headers);
        
        if ($shouldSucceed) {
            if ($response && $response['success']) {
                echo "✅ PASS\n";
            } else {
                echo "❌ FAIL - Expected success but got: " . json_encode($response) . "\n";
            }
        } else {
            if (!$response || !$response['success']) {
                echo "✅ PASS (Correctly denied)\n";
            } else {
                echo "❌ FAIL - Expected failure but got success\n";
            }
        }
    }

    private function testUpdateTheater($userType, $token, $shouldSucceed = true)
    {
        echo "  ✏️  Testing update theater as {$userType}... ";
        
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        $updateData = [
            'name' => 'Updated Theater Name',
            'address' => '456 Updated Street',
            'phone' => '0987654321',
            'email' => 'updated@theater.com',
            'description' => 'Updated description',
            'is_active' => true
        ];
        
        $response = $this->makeRequest('PUT', '/admin/theaters/1', $updateData, $headers);
        
        if ($shouldSucceed) {
            if ($response && $response['success']) {
                echo "✅ PASS\n";
            } else {
                echo "❌ FAIL - Expected success but got: " . json_encode($response) . "\n";
            }
        } else {
            if (!$response || !$response['success']) {
                echo "✅ PASS (Correctly denied)\n";
            } else {
                echo "❌ FAIL - Expected failure but got success\n";
            }
        }
    }

    private function testDeleteTheater($userType, $token, $shouldSucceed = true)
    {
        echo "  🗑️  Testing delete theater as {$userType}... ";
        
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        $response = $this->makeRequest('DELETE', '/admin/theaters/1', null, $headers);
        
        if ($shouldSucceed) {
            if ($response && $response['success']) {
                echo "✅ PASS\n";
            } else {
                echo "❌ FAIL - Expected success but got: " . json_encode($response) . "\n";
            }
        } else {
            if (!$response || !$response['success']) {
                echo "✅ PASS (Correctly denied)\n";
            } else {
                echo "❌ FAIL - Expected failure but got success\n";
            }
        }
    }

    private function testScheduleEndpoints()
    {
        echo "📅 Testing Schedule Endpoints...\n";
        
        // Test 1: Admin can create schedule
        $this->testCreateSchedule('admin', $this->adminToken);
        
        // Test 2: Movie Manager can create schedule
        $this->testCreateSchedule('movie_manager', $this->movieManagerToken);
        
        // Test 3: Regular user cannot create schedule
        $this->testCreateSchedule('regular_user', $this->regularToken, false);
        
        // Test 4: Admin can update schedule
        $this->testUpdateSchedule('admin', $this->adminToken);
        
        // Test 5: Admin can delete schedule
        $this->testDeleteSchedule('admin', $this->adminToken);
        
        echo "\n";
    }

    private function testCreateSchedule($userType, $token, $shouldSucceed = true)
    {
        echo "  ➕ Testing create schedule as {$userType}... ";
        
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        $scheduleData = [
            'movie_id' => 1,
            'theater_id' => 1,
            'room_name' => 'Room 1',
            'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'price' => 100000,
            'status' => 'active'
        ];
        
        $response = $this->makeRequest('POST', '/schedules', $scheduleData, $headers);
        
        if ($shouldSucceed) {
            if ($response && $response['success']) {
                echo "✅ PASS\n";
            } else {
                echo "❌ FAIL - Expected success but got: " . json_encode($response) . "\n";
            }
        } else {
            if (!$response || !$response['success']) {
                echo "✅ PASS (Correctly denied)\n";
            } else {
                echo "❌ FAIL - Expected failure but got success\n";
            }
        }
    }

    private function testUpdateSchedule($userType, $token, $shouldSucceed = true)
    {
        echo "  ✏️  Testing update schedule as {$userType}... ";
        
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        $updateData = [
            'movie_id' => 1,
            'theater_id' => 1,
            'room_name' => 'Room 2',
            'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'price' => 120000,
            'status' => 'active'
        ];
        
        $response = $this->makeRequest('PUT', '/schedules/1', $updateData, $headers);
        
        if ($shouldSucceed) {
            if ($response && $response['success']) {
                echo "✅ PASS\n";
            } else {
                echo "❌ FAIL - Expected success but got: " . json_encode($response) . "\n";
            }
        } else {
            if (!$response || !$response['success']) {
                echo "✅ PASS (Correctly denied)\n";
            } else {
                echo "❌ FAIL - Expected failure but got success\n";
            }
        }
    }

    private function testDeleteSchedule($userType, $token, $shouldSucceed = true)
    {
        echo "  🗑️  Testing delete schedule as {$userType}... ";
        
        $headers = [];
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        $response = $this->makeRequest('DELETE', '/schedules/1', null, $headers);
        
        if ($shouldSucceed) {
            if ($response && $response['success']) {
                echo "✅ PASS\n";
            } else {
                echo "❌ FAIL - Expected success but got: " . json_encode($response) . "\n";
            }
        } else {
            if (!$response || !$response['success']) {
                echo "✅ PASS (Correctly denied)\n";
            } else {
                echo "❌ FAIL - Expected failure but got success\n";
            }
        }
    }

    private function makeRequest($method, $endpoint, $data = null, $headers = [])
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $headers));
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            return null;
        }
        
        $decodedResponse = json_decode($response, true);
        
        // Log request details
        echo "    🔍 {$method} {$endpoint} -> HTTP {$httpCode}\n";
        
        return $decodedResponse;
    }
}

// Chạy tests
$tester = new TheaterApiTester();
$tester->runAllTests();
