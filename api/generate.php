// api/generate.php
<?php
header('Content-Type: application/json');
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['userInfo']) || !isset($data['jobDescription'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

$prompt = generatePrompt($data['userInfo'], $data['jobDescription']);

$response = callGeminiAPI($prompt);
echo json_encode($response);

function generatePrompt($userInfo, $jobDescription) {
    return "
Please write a professional cover letter for a job application with the following details:

APPLICANT INFORMATION:
Name: {$userInfo['name']}
Key Skills: {$userInfo['skills']}
Relevant Experience: {$userInfo['experience']}

JOB DESCRIPTION:
{$jobDescription}

INSTRUCTIONS:
1. Write a compelling cover letter that matches my skills and experience to the job requirements
2. Use a professional but engaging tone
3. Structure the letter with clear paragraphs:
   - Opening paragraph introducing myself and stating the position I'm applying for
   - 1-2 paragraphs highlighting relevant skills and experience
   - Closing paragraph expressing interest in an interview
4. Keep the length appropriate for a cover letter (around 300-400 words)
5. Don't use generic phrases - make it specific to my background and the role
6. Include a formal header with my name
7. Use a professional sign-off";
}

function callGeminiAPI($prompt) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . GEMINI_API_KEY;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['error' => 'Failed to generate cover letter'];
    }

    $responseData = json_decode($response, true);
    return [
        'coverLetter' => $responseData['candidates'][0]['content']['parts'][0]['text']
    ];
}
?>