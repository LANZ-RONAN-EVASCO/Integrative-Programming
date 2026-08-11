<?php
// Group Exercise #2 - One Page PHP + HTML + CSS + JavaScript Application

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meet Our Team | Group Exercise #2</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b, #2563eb);
            color: #fff;
            overflow-x: hidden;
        }

        header {
            text-align: center;
            padding: 55px 20px 30px;
        }

        header h1 {
            font-size: clamp(34px, 5vw, 48px);
            margin-bottom: 8px;
        }

        header p {
            color: #d1d5db;
            font-size: 16px;
        }

        .controls {
            width: 92%;
            max-width: 1500px;
            margin: 0 auto 25px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .controls input,
        .controls button {
            border: 0;
            border-radius: 25px;
            padding: 11px 17px;
            font-size: 14px;
        }

        .controls input {
            width: min(320px, 90vw);
            outline: none;
        }

        .controls button {
            cursor: pointer;
            color: #fff;
            background: #2563eb;
            transition: .25s;
        }

        .controls button:hover {
            transform: translateY(-2px);
            background: #1d4ed8;
        }

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
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 25px;
            padding: 26px 20px;
            text-align: center;
            transition: .35s;
        }

        .card:nth-child(5) { grid-column: 2 / span 3; }
        .card:nth-child(6) { grid-column: 5 / span 3; }
        .card:nth-child(7) { grid-column: 8 / span 3; }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 45px rgba(0,0,0,.35);
        }

        .card img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
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

        .social {
            margin-top: 15px;
        }

        .social a {
            text-decoration: none;
            color: #fff;
            border: 1px solid rgba(255,255,255,.3);
            padding: 9px 16px;
            border-radius: 30px;
            display: inline-block;
            transition: .3s;
        }

        .social a:hover {
            background: #fff;
            color: #2563eb;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            display: none;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #cbd5e1;
            background: rgba(0,0,0,.25);
        }

        @media (max-width: 1100px) {
            .card,
            .card:nth-child(5),
            .card:nth-child(6),
            .card:nth-child(7) {
                grid-column: span 6;
            }
        }

        @media (max-width: 600px) {
            header {
                padding-top: 40px;
            }

            .team {
                width: 90%;
                grid-template-columns: 1fr;
            }

            .card,
            .card:nth-child(5),
            .card:nth-child(6),
            .card:nth-child(7) {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>
    <header>
        <h1>Meet Our Team</h1>
        <p>BS Information Technology | Web Technology Project</p>
    </header>

    <!-- JavaScript search/filter control -->
    <div class="controls">
        <input type="text" id="searchBox" placeholder="Search team member or role..." aria-label="Search team members">
        <button type="button" id="showAll">Show All</button>
    </div>

    <main class="team" id="teamContainer">
        <?php foreach ($members as $member): ?>
            <article class="card"
                     data-name="<?= htmlspecialchars(strtolower($member["name"])) ?>"
                     data-role="<?= htmlspecialchars(strtolower($member["role"])) ?>">
                <img src="<?= htmlspecialchars($member["image"]) ?>"
                     alt="<?= htmlspecialchars($member["name"]) ?>">

                <h2><?= htmlspecialchars($member["name"]) ?></h2>
                <div class="role"><?= htmlspecialchars($member["role"]) ?></div>

                <div class="info">
                    <b>Course:</b> <?= htmlspecialchars($member["course"]) ?><br>
                    <b>Year:</b> <?= htmlspecialchars($member["year"]) ?><br>
                    <b>Email:</b> <?= htmlspecialchars($member["email"]) ?>
                </div>

                <div>
                    <?php foreach ($member["skills"] as $skill): ?>
                        <span class="skill"><?= htmlspecialchars($skill) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="social">
                    <?php if ($member["facebook"] !== "#"): ?>
                        <a href="<?= htmlspecialchars($member["facebook"]) ?>" target="_blank" rel="noopener noreferrer">
                            Facebook
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="return false;">Facebook</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>

        <div class="no-results" id="noResults">No team member found.</div>
    </main>

    <footer>
        Designed by BSIT Students • Web Technology Project © <?= date("Y") ?>
    </footer>

    <script>
       //search filter
        const searchBox = document.getElementById("searchBox");
        const cards = document.querySelectorAll(".card");
        const noResults = document.getElementById("noResults");
        const showAll = document.getElementById("showAll");

        function filterMembers() {
            const keyword = searchBox.value.toLowerCase().trim();
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.dataset.name;
                const role = card.dataset.role;
                const match = name.includes(keyword) || role.includes(keyword);

                card.style.display = match ? "" : "none";

                if (match) {
                    visibleCount++;
                }
            });

            noResults.style.display = visibleCount === 0 ? "block" : "none";
        }

        searchBox.addEventListener("input", filterMembers);

        showAll.addEventListener("click", () => {
            searchBox.value = "";
            filterMembers();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    </script>
</body>
</html>