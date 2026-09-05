<?php

$members = [
    [
        "name" => "Alexander Hugo",
        "role" => "Team Leader",
        "course" => "BS Information Technology",
        "year" => "3rd Year",
        "email" => "hugoalexander_bsit@plmun.edu.ph",
        "image" => "Images/member1.jpg",
        "skills" => ["Python", "HTML", "CSS", "Java", "C++"],
        "facebook" => "https://www.facebook.com/alexander.hugo.54390"
    ],
    [
        "name" => "Jasper Leonardo",
        "role" => "Front-end Developer",
        "course" => "BSIT",
        "year" => "3rd Year",
        "email" => "leonardojasper_bsit@plmun.edu.ph",
        "image" => "Images/member2.jpg",
        "skills" => ["HTML", "CSS", "JavaScript"],
        "facebook" => "https://www.facebook.com/truabnessy"
    ],
    [
        "name" => "Jefferson Lumbo",
        "role" => "Back-end Developer",
        "course" => "BSIT",
        "year" => "3rd Year",
        "email" => "lumbojefferson_bsit@plmun.edu.ph",
        "image" => "Images/member3.jpg",
        "skills" => ["PHP", "MySQL", "Java"],
        "facebook" => "https://www.facebook.com/lumbojefferson"
    ],
    [
        "name" => "Mark Laurence Marquez",
        "role" => "UI/UX Designer",
        "course" => "BSIT",
        "year" => "3rd Year",
        "email" => "marquezmarklaurence_bsit@plmun.edu.ph",
        "image" => "Images/member4.jpg",
        "skills" => ["Figma", "Photoshop"],
        "facebook" => "https://www.facebook.com/launzz.mrk"
    ],
    [
        "name" => "Lanz Evasco",
        "role" => "Documentation",
        "course" => "BSIT",
        "year" => "3rd Year",
        "email" => "evascolanzronan_bsit@plmun.edu.ph",
        "image" => "Images/member5.jpg",
        "skills" => ["MS Word", "Research"],
        "facebook" => "https://www.facebook.com/lanzronanalmeida.evasco"
    ],
    [
        "name" => "Genesis Engay",
        "role" => "Quality Assurance",
        "course" => "BSIT",
        "year" => "3rd Year",
        "email" => "engaygenesisreymark_bsit@plmun.edu.ph",
        "image" => "Images/member6.jpg",
        "skills" => ["Testing", "Documentation"],
        "facebook" => "https://www.facebook.com/genesisreymark.litigar"
    ],
    [
        "name" => "Mica Grace Mendez",
        "role" => "Project Manager",
        "course" => "BSIT",
        "year" => "3rd Year",
        "email" => "mendezmicagrace_bsit@plmun.edu.ph",
        "image" => "Images/member7.jpg",
        "skills" => ["HTML", "CSS"],
        "facebook" => "#"
    ]
];


/* ==========================================
   TEAM SEARCH USING GET
========================================== */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

$filteredMembers = [];

foreach ($members as $member) {

    if (
        $search === "" ||
        stripos($member["name"], $search) !== false ||
        stripos($member["role"], $search) !== false
    ) {
        $filteredMembers[] = $member;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meet Our Team | PHP GET Method</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", sans-serif;
    }

    body {
        min-height: 100vh;
        background: linear-gradient(135deg, #0f172a, #1e293b, #2563eb);
        color: white;
    }


    /* =========================
           HEADER
        ========================= */

    header {
        text-align: center;
        padding: 55px 20px 40px;
    }

    header h1 {
        font-size: clamp(34px, 5vw, 48px);
        margin-bottom: 8px;
    }

    header p {
        color: #d1d5db;
    }


    /* =========================
           REPORT BUTTON
        ========================= */

    .report-button {
        display: inline-block;

        margin-top: 20px;

        padding: 12px 25px;

        background: #2563eb;

        color: white;

        text-decoration: none;

        border-radius: 30px;

        font-size: 14px;

        font-weight: 600;

        transition: 0.3s;

        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    }

    .report-button:hover {
        background: #1d4ed8;

        transform: translateY(-3px);

        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.35);
    }


    /* =========================
           SEARCH
        ========================= */

    .search-section {
        width: 90%;

        max-width: 700px;

        margin: 0 auto 35px;
    }

    .search-form {
        display: flex;

        gap: 10px;
    }

    .search-form input {
        flex: 1;

        padding: 12px 16px;

        border: none;

        border-radius: 25px;

        outline: none;

        font-size: 14px;
    }

    .search-form button {
        padding: 12px 22px;

        border: none;

        border-radius: 25px;

        background: #2563eb;

        color: white;

        cursor: pointer;

        font-weight: 600;
    }

    .search-form button:hover {
        background: #1d4ed8;
    }


    /* =========================
           TEAM
        ========================= */

    .team {
        width: 92%;

        max-width: 1500px;

        margin: 0 auto;

        display: grid;

        grid-template-columns: repeat(12, 1fr);

        gap: 25px;

        padding-bottom: 60px;
    }

    .card {
        grid-column: span 3;

        background: rgba(255, 255, 255, 0.08);

        backdrop-filter: blur(15px);

        border: 1px solid rgba(255, 255, 255, 0.15);

        border-radius: 25px;

        padding: 26px 20px;

        text-align: center;

        transition: 0.35s;
    }

    .card:hover {
        transform: translateY(-10px);

        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
    }

    .card img {
        width: 140px;

        height: 140px;

        border-radius: 50%;

        object-fit: cover;

        border: 5px solid white;

        margin-bottom: 18px;
    }

    .card h2 {
        font-size: 20px;

        margin-bottom: 6px;
    }

    .role {
        color: #60a5fa;

        font-weight: 600;

        margin-bottom: 15px;
    }

    .info {
        text-align: left;

        line-height: 1.8;

        color: #e5e7eb;

        margin-bottom: 16px;

        font-size: 12px;

        overflow-wrap: anywhere;
    }

    .skill {
        display: inline-block;

        background: #2563eb;

        padding: 7px 14px;

        margin: 4px 2px;

        border-radius: 30px;

        font-size: 12px;
    }


    /* =========================
           SOCIAL
        ========================= */

    .social {
        margin-top: 15px;
    }

    .social a {
        text-decoration: none;

        color: white;

        border: 1px solid rgba(255, 255, 255, 0.3);

        padding: 9px 16px;

        border-radius: 30px;

        display: inline-block;
    }

    .social a:hover {
        background: white;

        color: #2563eb;
    }


    /* =========================
           NO RESULTS
        ========================= */

    .no-results {
        grid-column: 1 / -1;

        text-align: center;

        padding: 40px;
    }


    /* =========================
           FOOTER
        ========================= */

    footer {
        text-align: center;

        padding: 25px;

        color: #cbd5e1;

        background: rgba(0, 0, 0, 0.25);
    }


    /* =========================
           RESPONSIVE
        ========================= */

    @media (max-width: 1100px) {

        .card {
            grid-column: span 6;
        }

    }

    @media (max-width: 600px) {

        .team {
            grid-template-columns: 1fr;
        }

        .card {
            grid-column: auto;
        }

        .search-form {
            flex-direction: column;
        }

        .search-form button {
            width: 100%;
        }

        .report-button {
            width: 100%;
            max-width: 250px;
        }

    }
    </style>

</head>

<body>


    <!-- =========================
         HEADER
    ========================= -->

    <header>

        <h1>Meet Our Team</h1>

        <p>BS Information Technology | Web Technology Project</p>

        <!-- REPORT BUTTON -->

        <a href="report.php" class="report-button" target="_blank" rel="noopener noreferrer">

            Report a Problem

        </a>

    </header>


    <!-- =========================
         SEARCH TEAM MEMBERS
    ========================= -->

    <section class="search-section">

        <form method="GET" action="" class="search-form">

            <input type="text" name="search" placeholder="Search team member or role..."
                value="<?= htmlspecialchars($search) ?>">

            <button type="submit">
                Search
            </button>

        </form>

    </section>


    <!-- =========================
         TEAM MEMBERS
    ========================= -->

    <main class="team">

        <?php if (count($filteredMembers) > 0): ?>

        <?php foreach ($filteredMembers as $member): ?>

        <article class="card">

            <img src="<?= htmlspecialchars($member["image"]) ?>" alt="<?= htmlspecialchars($member["name"]) ?>">

            <h2>
                <?= htmlspecialchars($member["name"]) ?>
            </h2>

            <div class="role">
                <?= htmlspecialchars($member["role"]) ?>
            </div>

            <div class="info">

                <b>Course:</b>
                <?= htmlspecialchars($member["course"]) ?>

                <br>

                <b>Year:</b>
                <?= htmlspecialchars($member["year"]) ?>

                <br>

                <b>Email:</b>
                <?= htmlspecialchars($member["email"]) ?>

            </div>


            <?php foreach ($member["skills"] as $skill): ?>

            <span class="skill">
                <?= htmlspecialchars($skill) ?>
            </span>

            <?php endforeach; ?>


            <div class="social">

                <?php if ($member["facebook"] !== "#"): ?>

                <a href="<?= htmlspecialchars($member["facebook"]) ?>" target="_blank" rel="noopener noreferrer">
                    Facebook
                </a>

                <?php else: ?>

                <a href="#" onclick="return false;">
                    Facebook
                </a>

                <?php endif; ?>

            </div>

        </article>

        <?php endforeach; ?>

        <?php else: ?>

        <div class="no-results">

            <h2>
                No team member found.
            </h2>

            <p>
                Try searching for another name or role.
            </p>

        </div>

        <?php endif; ?>

    </main>


    <!-- =========================
         FOOTER
    ========================= -->

    <footer>

        Designed by BSIT Students • Web Technology Project

        <br>

        © <?= date("Y") ?>

    </footer>


</body>

</html>