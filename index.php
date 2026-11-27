
<?php
session_start();

// Menyimpan skor tertinggi
if (!isset($_SESSION['highscore'])) {
    $_SESSION['highscore'] = 0;
}

// Menerima skor dari JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = (int) ($_POST['score'] ?? 0);

    if ($score > $_SESSION['highscore']) {
        $_SESSION['highscore'] = $score;
    }

    echo $_SESSION['highscore'];
    exit;
}

$highscore = $_SESSION['highscore'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snake Game</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="game-container">

    <h1>🐍 SNAKE GAME</h1>
    <p class="subtitle">Classic Arcade Edition</p>

    <div class="score-board">
        <div>
            <span>SCORE</span>
            <h2 id="score">0</h2>
        </div>

        <div>
            <span>BEST SCORE</span>
            <h2 id="highscore">
                <?= htmlspecialchars((string)$highscore) ?>
            </h2>
        </div>
    </div>

    <div class="game-area">
        <canvas id="gameCanvas"
                width="400"
                height="400"></canvas>

        <div id="overlay" class="overlay">
            <h2 id="message">READY?</h2>
            <p id="description">Tekan tombol START untuk bermain</p>
            <button onclick="startGame()">START GAME</button>
        </div>
    </div>

    <div class="controls">
        <button onclick="pauseGame()">⏸ PAUSE</button>
        <button onclick="resetGame()">↻ RESTART</button>
    </div>

    <p class="instruction">
        Gunakan tombol ⬆ ⬇ ⬅ ➡ untuk menggerakkan ular
    </p>

</div>

<script>

// Mengambil elemen HTML
const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

const scoreText = document.getElementById("score");
const highscoreText = document.getElementById("highscore");

const overlay = document.getElementById("overlay");
const message = document.getElementById("message");
const description = document.getElementById("description");

// Pengaturan game
const box = 20;
const total = canvas.width / box;

let snake;
let food;
let dx;
let dy;
let score = 0;
let gameInterval = null;
let running = false;
let directionLocked = false;

// Mengatur posisi awal
function setupGame() {

    snake = [
        {x: 200, y: 200},
        {x: 180, y: 200},
        {x: 160, y: 200}
    ];

    dx = box;
    dy = 0;

    score = 0;
    scoreText.textContent = score;

    generateFood();
    drawGame();
}

// Membuat makanan secara acak
function generateFood() {

    do {
        food = {
            x: Math.floor(Math.random() * total) * box,
            y: Math.floor(Math.random() * total) * box
        };
    } while (
        snake && snake.some(part =>
            part.x === food.x && part.y === food.y
        )
    );
}

// Menggambar game
function drawGame() {

    ctx.fillStyle = "#101827";
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Menggambar grid
    ctx.strokeStyle = "#1d2a3d";

    for (let i = 0; i <= canvas.width; i += box) {
        ctx.beginPath();
        ctx.moveTo(i, 0);
        ctx.lineTo(i, canvas.height);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(0, i);
        ctx.lineTo(canvas.width, i);
        ctx.stroke();
    }

    // Menggambar ular
    snake.forEach((part, index) => {

        ctx.fillStyle = index === 0 ? "#a3ff12" : "#55c900";

        ctx.fillRect(
            part.x + 1,
            part.y + 1,
            box - 2,
            box - 2
        );
    });

    // Menggambar makanan
    ctx.fillStyle = "#ff4757";
    ctx.beginPath();

    ctx.arc(
        food.x + box / 2,
        food.y + box / 2,
        box / 2 - 2,
        0,
        Math.PI * 2
    );

    ctx.fill();
}

// Logika pergerakan ular
function updateGame() {

    if (!running) return;

    directionLocked = false;

    const head = {
        x: snake[0].x + dx,
        y: snake[0].y + dy
    };

    const ateFood =
        head.x === food.x && head.y === food.y;

    // Deteksi tabrakan dengan dinding
    if (
        head.x < 0 ||
        head.x >= canvas.width ||
        head.y < 0 ||
        head.y >= canvas.height
    ) {
        endGame();
        return;
    }

    // Deteksi tabrakan dengan tubuh sendiri
    const bodyToCheck = ateFood
        ? snake
        : snake.slice(0, -1);

    if (bodyToCheck.some(part =>
        part.x === head.x && part.y === head.y
    )) {
        endGame();
        return;
    }

    snake.unshift(head);

    if (ateFood) {

        score += 10;
        scoreText.textContent = score;

        generateFood();

    } else {

        snake.pop();
    }

    drawGame();
}

// Menjalankan game
function startGame() {

    if (running) return;

    if (!snake) {
        setupGame();
    }

    overlay.style.display = "none";
    running = true;

    gameInterval = setInterval(updateGame, 120);
}

// Pause game
function pauseGame() {

    if (!running) return;

    running = false;
    clearInterval(gameInterval);

    message.textContent = "PAUSED";
    description.textContent = "Tekan START untuk melanjutkan";
    overlay.style.display = "flex";
}

// Restart game
function resetGame() {

    clearInterval(gameInterval);
    running = false;

    setupGame();

    message.textContent = "READY?";
    description.textContent = "Tekan START untuk bermain";
    overlay.style.display = "flex";
}

// Game over
function endGame() {

    running = false;
    clearInterval(gameInterval);

    saveScore();

    message.textContent = "GAME OVER!";
    description.textContent = "Skor kamu: " + score;

    overlay.style.display = "flex";
}

// Menyimpan skor ke PHP Session
function saveScore() {

    fetch("index.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "score=" + score
    })
    .then(response => response.text())
    .then(result => {
        highscoreText.textContent = result;
    });
}

// Mengontrol ular menggunakan WASD dan Arrow Keys
document.addEventListener("keydown", function(event) {

    if (directionLocked || !running) return;

    const key = event.key.toLowerCase();

    const controls = [
        "arrowup",
        "arrowdown",
        "arrowleft",
        "arrowright",
        "w", "a", "s", "d"
    ];

    if (controls.includes(key)) {
        event.preventDefault();
    }

    // Atas: W atau Arrow Up
    if ((key === "w" || key === "arrowup") && dy === 0) {
        dx = 0;
        dy = -box;
        directionLocked = true;
    }

    // Bawah: S atau Arrow Down
    else if ((key === "s" || key === "arrowdown") && dy === 0) {
        dx = 0;
        dy = box;
        directionLocked = true;
    }

    // Kiri: A atau Arrow Left
    else if ((key === "a" || key === "arrowleft") && dx === 0) {
        dx = -box;
        dy = 0;
        directionLocked = true;
    }

    // Kanan: D atau Arrow Right
    else if ((key === "d" || key === "arrowright") && dx === 0) {
        dx = box;
        dy = 0;
        directionLocked = true;
    }

});



// Menyiapkan game pertama kali
setupGame();

</script>

</body>
</html>
