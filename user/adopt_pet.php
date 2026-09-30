<?php
/**
 * PawPal - Adoption Request Handler
 */
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRF($_POST['csrf_token'])) {
        setFlash('danger', 'Invalid security token (CSRF). Please try again.');
        redirect(BASE_URL . '/user/pets.php');
    }

    $userId = $_SESSION['user_id'];
    $petId = isset($_POST['pet_id']) ? (int)$_POST['pet_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    // 2. Validate Inputs
    if ($petId <= 0 || empty($message)) {
        setFlash('danger', 'Please provide a valid pet and adoption message.');
        redirect(BASE_URL . '/user/pets.php');
    }

    // 3. Fetch pet and verify availability
    $pet = getPetById($pdo, $petId);
    if (!$pet) {
        setFlash('danger', 'The selected pet does not exist.');
        redirect(BASE_URL . '/user/pets.php');
    }

    if ($pet['status'] !== 'available') {
        setFlash('danger', "Sorry, {$pet['pet_name']} has already been adopted or is no longer available.");
        redirect(BASE_URL . '/user/pet_details.php?id=' . $petId);
    }

    // 4. Check for existing pending request
    $stmt = $pdo->prepare("SELECT id FROM adoptions WHERE user_id = ? AND pet_id = ? AND adoption_status = 'pending'");
    $stmt->execute([$userId, $petId]);
    if ($stmt->fetch()) {
        setFlash('warning', "You already have a pending adoption request for {$pet['pet_name']}.");
        redirect(BASE_URL . '/user/pet_details.php?id=' . $petId);
    }

    // 5. Insert adoption request
    try {
        $stmt = $pdo->prepare("INSERT INTO adoptions (user_id, pet_id, message, adoption_status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$userId, $petId, $message]);

        // Create a user notification confirming submission
        createNotification(
            $pdo, 
            $userId, 
            $petId, 
            "📝 Your adoption request for {$pet['pet_name']} has been submitted! An admin will review it soon.", 
            'info'
        );

        setFlash('success', "🎉 Your adoption request for <strong>{$pet['pet_name']}</strong> has been submitted successfully! We will notify you once it's reviewed.");
        redirect(BASE_URL . '/user/pet_details.php?id=' . $petId);

    } catch (PDOException $e) {
        setFlash('danger', 'An error occurred while submitting your request. Please try again later.');
        redirect(BASE_URL . '/user/pet_details.php?id=' . $petId);
    }

} else {
    // Redirect GET requests back to pets index
    redirect(BASE_URL . '/user/pets.php');
}
