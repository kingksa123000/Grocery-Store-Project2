<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['products']) && is_array($input['products'])) {
        $errors = [];
        $has_error = false;

        foreach ($input['products'] as $productInfo) {
            $productName = $productInfo['name'];
            $orderedQuantity = $productInfo['ordered'];

            try {
                $stmt = $conn->prepare("SELECT quantity FROM products WHERE name = ?"); // 
                $stmt->execute([$productName]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $currentStock = $product['quantity'];
                    if ($orderedQuantity > $currentStock) {
                        $errors[] = [
                            'name' => $productName,
                            'ordered' => $orderedQuantity,
                            'stock' => $currentStock,
                        ];
                        $has_error = true;
                    }
                } else {
                    $errors[] = [
                        'name' => $productName,
                        'ordered' => $orderedQuantity,
                        'stock' => 0, // Product not found
                    ];
                    $has_error = true;
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
        }

        echo json_encode(['has_error' => $has_error, 'errors' => $errors]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request.']);
?>