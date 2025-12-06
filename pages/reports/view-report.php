<?php
// Get the file path from the URL. It's already been generated.
$filePath = $_GET['filePath'] ?? '';

// Construct the correct relative path from this file's location to the project root.
$pdf_url = '../../' . htmlspecialchars($filePath);
?>

<div class="bg-[#F1F7F9] rounded h-[80vh]">
    <object data="<?php echo $pdf_url; ?>" type="application/pdf" width="100%" height="100%">
        <p>Your browser does not support PDFs. <a href="<?php echo $pdf_url; ?>">Download the PDF</a>.</p>
    </object>
</div>