import express from "express";
import path from "path";

const app = express();
const PORT = 3000;

app.use(express.static(path.join(process.cwd(), "public")));

// API endpoint for world chronometer time computation
app.get("/api/time", (req, res) => {
  const WORLD_EPOCH = new Date("2026-06-01T00:00:00Z").getTime();
  const SPEED = 5;
  const MONTHS = [
    "Лютохлад", "Ветровей", "Таловод",
    "Зеленорост", "Цветель", "Влажник",
    "Ярило", "Зрельник", "Страдник",
    "Листопад", "Грязовец", "Студень"
  ];

  const now = Date.now();
  const elapsedSec = (now - WORLD_EPOCH) / 1000;
  const worldSec = elapsedSec * SPEED;
  const worldDays = Math.floor(worldSec / 86400);
  const year = Math.floor(worldDays / 360) + 1;
  const dayOfYear = ((worldDays % 360) + 360) % 360;
  const month = Math.floor(dayOfYear / 30);
  const day = (dayOfYear % 30) + 1;
  const secOfDay = Math.floor(((worldSec % 86400) + 86400) % 86400);
  const h = Math.floor(secOfDay / 3600);
  const m = Math.floor((secOfDay % 3600) / 60);
  const s = Math.floor(secOfDay % 60);
  const isDay = h >= 6 && h < 18;

  res.json({
    epoch: WORLD_EPOCH,
    speed: SPEED,
    time: `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`,
    date: `${String(day).padStart(2, "0")}.${String(month + 1).padStart(2, "0")}.${String(year).padStart(2, "0")}`,
    day,
    month: month + 1,
    year,
    monthName: MONTHS[month] || MONTHS[0],
    isDay,
    cycle: isDay ? "ДЕНЬ ИСТОКА" : "НОЧЬ ИСТОКА"
  });
});

app.get("*", (req, res) => {
  res.sendFile(path.join(process.cwd(), "public", "index.html"));
});

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Server running at http://0.0.0.0:${PORT}`);
});
