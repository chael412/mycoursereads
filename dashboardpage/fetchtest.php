<?php
// API URL
$apiUrl = "https://me.vivliotek.com/api/books";

// Use cURL to fetch data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo "Error fetching data: " . curl_error($ch);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Decode JSON response
$books = json_decode($response, true);

// Check if 'books' and 'data' keys exist
if (isset($books['books']['data']) && is_array($books['books']['data'])) {
    echo "<h2>Library Books from Vivliotek</h2>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr>
            <th>Title</th>
            <th>Authors</th>
            <th>Call Number</th>
            <th>Volume</th>
            <th>Year of Publication</th>
            <th>Material</th>
          </tr>";

    foreach ($books['books']['data'] as $book) {
        // Get authors
        $authors = isset($book['authors']) ? implode(', ', array_column($book['authors'], 'author_name')) : 'N/A';

        // Get material name
        $material = isset($book['material']['material_name']) ? $book['material']['material_name'] : 'N/A';

        // Check volume properly
        $volume = isset($book['volume']) && $book['volume'] !== null ? $book['volume'] : 'N/A';

        echo "<tr>
                <td>{$book['title']}</td>
                <td>{$authors}</td>
                <td>{$book['call_no']}</td>
                <td>{$volume}</td>
                <td>{$book['year_publication']}</td>
                <td>{$material}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No books found from the API.</p>";
}
?>