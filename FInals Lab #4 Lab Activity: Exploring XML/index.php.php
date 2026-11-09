
<?php

libxml_use_internal_errors(true);

$xml = simplexml_load_file(__DIR__ . "/student_data.xml");

if ($xml === false) {
    echo "XML Loading Error:<br>";

    foreach (libxml_get_errors() as $error) {
        echo htmlspecialchars($error->message) . "<br>";
    }

    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>XML Student Records</title>
</head>
<body>

<h2>STUDENT INFORMATION RECORDS</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Course</th>
        <th>Year</th>
    </tr>

    <?php foreach ($xml->student as $student): ?>
    <tr>
        <td><?= htmlspecialchars((string)$student->id) ?></td>
        <td><?= htmlspecialchars((string)$student->name) ?></td>
        <td><?= htmlspecialchars((string)$student->course) ?></td>
        <td><?= htmlspecialchars((string)$student->year) ?></td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
