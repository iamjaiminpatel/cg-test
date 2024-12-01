<?php
echo "sdfsdfsd";exit;
// Include the AWS SDK (you can manually include this if you're not using Composer)
require 'vendor/autoload.php'; // Adjust the path if needed

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// AWS credentials
$aws_access_key_id = 'your-access-key-id';
$aws_secret_access_key = 'your-secret-access-key';
$bucket_name = 'your-bucket-name';
$region = 'your-region';

// Initialize S3 Client
$client = new S3Client([
    'version' => 'latest',
    'region' => $region,
    'credentials' => [
        'key' => $aws_access_key_id,
        'secret' => $aws_secret_access_key,
    ],
]);

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $image = $_FILES['image'];

    // Check for errors
    if ($image['error'] !== UPLOAD_ERR_OK) {
        echo "Error uploading file!";
        exit;
    }

    // Generate a unique name for the image
    $imageName = time() . '_' . basename($image['name']);
    $filePath = $image['tmp_name'];

    try {
        // Upload the image to S3
        $result = $client->putObject([
            'Bucket' => $bucket_name,
            'Key' => 'images/' . $imageName, // Optional: Store under 'images' folder
            'SourceFile' => $filePath,
            'ACL' => 'public-read', // Optional: Make the image public
        ]);

        // Get the URL of the uploaded image
        $url = $result['ObjectURL'];

        echo "Image uploaded successfully. URL: <a href='$url'>$url</a>";
    } catch (AwsException $e) {
        echo "Error uploading file: " . $e->getMessage();
    }
} else {
    echo "No image uploaded.";
}

?>

<!-- HTML Form for Image Upload -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
</head>
<body>
    <h1>Upload Image to S3</h1>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="file" name="image" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
