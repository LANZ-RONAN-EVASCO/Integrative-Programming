<?php

$name = "";
$email = "";
$problem = "";
$reportMessage = "";

if (isset($_GET["name"])) {
    $name = trim($_GET["name"]);
}

if (isset($_GET["email"])) {
    $email = trim($_GET["email"]);
}

if (isset($_GET["problem"])) {
    $problem = trim($_GET["problem"]);
}

if ($name !== "" && $email !== "" && $problem !== "") {
    $reportMessage = "Your problem report has been received.";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report a Problem</title>

    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #0f172a, #1e3a8a);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px;
    }

    .report-container {
        width: 100%;
        max-width: 600px;
        background: white;
        padding: 35px;
        border-radius: 20px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
    }

    h1 {
        text-align: center;
        color: #1e3a8a;
        margin-bottom: 10px;
    }

    .description {
        text-align: center;
        color: #666;
        margin-bottom: 30px;
    }

    .report-form {
        display: flex;
        flex-direction: column;
    }

    label {
        font-weight: bold;
        margin-bottom: 8px;
        color: #333;
    }

    input,
    textarea {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 15px;
        font-family: Arial, sans-serif;
    }

    input:focus,
    textarea:focus {
        outline: none;
        border-color: #2563eb;
    }

    textarea {
        resize: vertical;
    }

    button {
        border: none;
        padding: 13px;
        background: #2563eb;
        color: white;
        font-size: 16px;
        font-weight: bold;
        border-radius: 8px;
        cursor: pointer;
    }

    button:hover {
        background: #1d4ed8;
    }

    .success {
        margin-top: 25px;
        padding: 18px;
        background: #dcfce7;
        border: 1px solid #86efac;
        border-radius: 10px;
        color: #166534;
    }

    .success h3 {
        margin-bottom: 8px;
    }

    .problem-text {
        margin-top: 8px;
        padding: 10px;
        background: white;
        border-radius: 6px;
    }

    .back-button {
        display: block;
        text-align: center;
        margin-top: 20px;
        color: #2563eb;
        text-decoration: none;
        font-weight: bold;
    }

    .back-button:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>

    <div class="report-container">

        <h1>Report a Problem</h1>

        <p class="description">
            Please fill out the form below to report a problem with our website.
        </p>

        <form method="GET" action="" class="report-form">

            <label for="name">Name:</label>

            <input type="text" id="name" name="name" placeholder="Enter your name"
                value="<?= htmlspecialchars($name) ?>" required>

            <label for="email">E-mail:</label>

            <input type="email" id="email" name="email" placeholder="Enter your e-mail"
                value="<?= htmlspecialchars($email) ?>" required>

            <label for="problem">Problem:</label>

            <textarea id="problem" name="problem" rows="6" placeholder="Describe the problem..."
                required><?= htmlspecialchars($problem) ?></textarea>

            <button type="submit">
                Submit Report
            </button>

        </form>

        <?php if ($reportMessage !== ""): ?>

        <div class="success">

            <h3>Report Received</h3>

            <p>
                Thank you,
                <strong><?= htmlspecialchars($name) ?></strong>.
            </p>

            <p>
                We received your report:
            </p>

            <div class="problem-text">
                <?= htmlspecialchars($problem) ?>
            </div>

            <p style="margin-top: 10px;">
                Your email:
                <?= htmlspecialchars($email) ?>
            </p>

        </div>

        <?php endif; ?>

        <a href="index.php" class="back-button">
            ← Back to Meet Our Team
        </a>

    </div>

</body>

</html>