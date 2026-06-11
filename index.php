<?php

$WORLD_EPOCH = strtotime("2026-06-01 00:00:00 UTC");
$SPEED = 5;

$MONTHS = [
 "Лютохлад","Ветровей","Таловод",
 "Зеленорост","Цветель","Влажник",
 "Ярило","Зрельник","Страдник",
 "Листопад","Грязовец","Студень"
];

$now = time();
$elapsed = $now - $WORLD_EPOCH;

$worldSeconds = $elapsed * $SPEED;

$worldDays = floor($worldSeconds / 86400);

$year = floor($worldDays / 360) + 1;

$dayOfYear = $worldDays % 360;
$month = floor($dayOfYear / 30);
$day = ($dayOfYear % 30) + 1;

$secOfDay = $worldSeconds % 86400;

$h = floor($secOfDay / 3600);
$m = floor(($secOfDay % 3600) / 60);
$s = floor($secOfDay % 60);

$isDay = ($h >= 6 && $h < 18);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>ВЕК ИСТОКА</title>

<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@300;500;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">

<style>

body {
 margin:0;
 overflow:hidden;
 background:#05060a;
 color:#eae6dc;
 font-family:'Unbounded', sans-serif;
}

/* ===== фон ===== */
.bg {
 position:absolute;
 inset:0;
 background:
 radial-gradient(circle at var(--mx,50%) var(--my,50%), rgba(201,162,75,0.15), transparent 45%),
 linear-gradient(180deg,#05060a,#0a0d14);
}

/* ===== частицы ===== */
canvas {
 position:absolute;
 inset:0;
 pointer-events:none;
}

/* ===== центр ===== */
.center {
 position:absolute;
 top:50%;
 left:50%;
 transform:translate(-50%,-50%);
 text-align:center;
}

/* ===== время ===== */
.time {
 font-family:'Orbitron', monospace;
 font-size:120px;
 font-weight:700;
 letter-spacing:6px;
 color:#c9a24b;
 text-shadow:0 0 35px rgba(201,162,75,0.4);
}

/* ===== дата ===== */
.date {
 margin-top:10px;
 font-size:22px;
 opacity:0.9;
}

.epoch {
 margin-top:6px;
 font-size:14px;
 opacity:0.7;
 letter-spacing:2px;
}

/* ===== цикл ===== */
.cycle {
 margin-top:10px;
 font-size:13px;
 letter-spacing:5px;
 color:<?= $isDay ? "#c9a24b" : "#4aa3ff" ?>;
}

</style>
</head>

<body>

<div class="bg" id="bg"></div>
<canvas id="fx"></canvas>

<div class="center">

 <div class="time" id="time"></div>

 <div class="cycle" id="cycle">
  <?= $isDay ? "ДЕНЬ ИСТОКА" : "НОЧЬ ИСТОКА" ?>
 </div>

 <div class="date">
  <?= sprintf("%02d.%02d.%02d", $day, $month+1, $year) ?><br>
  <div class="epoch">Месяц: <?= $MONTHS[$month] ?> • Век Истока</div>
 </div>

</div>

<script>

/* =========================
   СТАБИЛЬНАЯ МОДЕЛЬ ВРЕМЕНИ
   ========================= */

/*
   ключ:
   мы НЕ "тикaем время"
   мы считаем его от epoch + speed
*/

const WORLD_EPOCH = <?= $WORLD_EPOCH ?> * 1000;
const SPEED = <?= $SPEED ?>;

function updateTime(){

 const now = Date.now();

 const elapsedSec = (now - WORLD_EPOCH) / 1000;

 const worldSec = elapsedSec * SPEED;

 let secOfDay = Math.floor(worldSec % 86400);

 let h = Math.floor(secOfDay / 3600);
 let m = Math.floor((secOfDay % 3600) / 60);
 let s = Math.floor(secOfDay % 60);

 document.getElementById("time").innerText =
 String(h).padStart(2,'0') + ":" +
 String(m).padStart(2,'0') + ":" +
 String(s).padStart(2,'0');

 requestAnimationFrame(updateTime);
}

updateTime();

/* =========================
   ПАРАЛЛАКС ФОНА
   ========================= */
document.addEventListener("mousemove",(e)=>{
 let x = (e.clientX / innerWidth) * 100;
 let y = (e.clientY / innerHeight) * 100;

 document.documentElement.style.setProperty("--mx", x);
 document.documentElement.style.setProperty("--my", y);
});

/* =========================
   ЧАСТИЦЫ ИСТОКА (ЧИСТЫЕ)
   ========================= */
const canvas = document.getElementById("fx");
const ctx = canvas.getContext("2d");

canvas.width = innerWidth;
canvas.height = innerHeight;

let p = [];

for(let i=0;i<120;i++){
 p.push({
  x:Math.random()*canvas.width,
  y:Math.random()*canvas.height,
  r:Math.random()*1.6,
  d:Math.random()*1.2
 });
}

function draw(){

 ctx.clearRect(0,0,canvas.width,canvas.height);

 ctx.fillStyle="rgba(201,162,75,0.18)";

 p.forEach(o=>{
  ctx.beginPath();
  ctx.arc(o.x,o.y,o.r,0,Math.PI*2);
  ctx.fill();

  o.y -= 0.25 + o.d;
  o.x += Math.sin(o.y*0.01)*0.3;

  if(o.y < 0){
   o.y = canvas.height;
   o.x = Math.random()*canvas.width;
  }
 });

 requestAnimationFrame(draw);
}

draw();

window.onresize = ()=>{
 canvas.width = innerWidth;
 canvas.height = innerHeight;
};

</script>

</body>
</html>