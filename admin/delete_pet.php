<?php
/**
 * PawPal Admin - Safe Delete Pet Processor
 */
require_once __DIR__ . '/../includes/functions.php';

// Enforce admin permission
requireAdmin();

$pet_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pet_id <= 0) {
    setFlash('danger', 'Invalid pet ID specified.');
    redirect(BASE_URL . '/admin/pets.php');
}

try {
    // 1. Fetch pet details to find the associated image file name
    $stmt = $pdo->prepare("SELECT pet_name, image FROM pets WHERE id = ?");
    $stmt->execute([$pet_id]);
    $pet = $stmt->fetch();
    
    if (!$pet) {
        setFlash('danger', 'The pet record you are trying to delete does not exist.');
        redirect(BASE_URL . '/admin/pets.php');
    }
    
    $pet_name = sanitize($pet['pet_name']);
    $image_filename = $pet['image'];

    // 2. Delete pet from the database (foreign key cascades will handle related adoptions/logs)
    $delete_stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
    $delete_stmt->execute([$pet_id]);

    // 3. Clean up the image file from the disk if it is a custom upload (starts with 'pet_')
    if (!empty($image_filename)) {
        $image_path = __DIR__ . '/../assets/images/' . $image_filename;
        if (file_exists($image_path) && strpos($image_filename, 'pet_') === 0) {
            @unlink($image_path);
        }
    }

    setFlash('success', "Successfully deleted pet record and all history for '$pet_name'.");

} catch (PDOException $e) {
    setFlash('danger', "Database failed to delete pet record: " . $e->getMessage());
}

redirect(BASE_URL . '/admin/pets.php');
?>
