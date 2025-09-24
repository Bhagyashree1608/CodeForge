  <?php
  session_start();
  include 'api/db.php';

  // Ensure user is logged in
  if(!isset($_SESSION['user_id'])){
      header("Location: login.php");
      exit;
  }
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QuizQuest – Select Subject</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
  body { background: #121212; font-family: 'Segoe UI', sans-serif; color:#fff; margin:0; padding:0; }
  .main-content { margin-left:280px; padding:40px 20px; min-height:100vh; }
  .container-card { background:#1f1f1f; border-radius:20px; padding:30px; max-width:900px; margin:auto; box-shadow:0 10px 25px rgba(0,0,0,0.5); }
  .card-subject { border-radius:15px; background:#2c2c2c; border:1px solid #444; padding:20px 15px; cursor:pointer; text-align:center; transition: transform 0.3s; }
  .card-subject:hover { transform: translateY(-5px) scale(1.03); }
  .card-subject i { font-size:2.2rem; color:#f39c12; margin-bottom:10px; }
  .card-subject h5 { color:#fff; font-weight:bold; margin-bottom:0; }
  .select-sub { background:#ff5722; color:#fff; border:none; padding:10px 20px; border-radius:10px; font-weight:bold; cursor:pointer; }
  .select-sub:hover { background:#e64a19; }
  #openQuiz { background:#607d8b; color:#fff; border:none; padding:10px 20px; border-radius:10px; cursor:pointer; }
  #openQuiz:hover { background:#455a64; }
  .selected-card { border:2px solid #ff5722; }
  h3 { text-align:center; margin-bottom:30px; }
  #subjects .col-md-4 { margin-bottom:20px; }
  @media(max-width:992px){ .main-content{ margin-left:0; padding:20px 10px; } }
  </style>
  </head>
  <body>

  <?php include 'sidebar.php'; ?>

  <div class="main-content">
      <div class="container-card">
          <h3>🎯 Choose Your Subject & Difficulty</h3>
          <div id="subjects" class="row gy-4">
              <div class="col-md-4">
                  <div class="card-subject" data-sub="coding">
                      <i class="bi bi-code-slash"></i>
                      <h5>Coding</h5>
                      <div class="mt-2"><button type="button" class="select-sub">Select</button></div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="card-subject" data-sub="finance">
                      <i class="bi bi-cash-stack"></i>
                      <h5>Finance</h5>
                      <div class="mt-2"><button type="button" class="select-sub">Select</button></div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="card-subject" data-sub="aptitude">
                      <i class="bi bi-calculator"></i>
                      <h5>Aptitude</h5>
                      <div class="mt-2"><button type="button" class="select-sub">Select</button></div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="card-subject" data-sub="science">
                      <i class="bi bi-puzzle"></i>
                      <h5>Reasoning</h5>
                      <div class="mt-2"><button type="button" class="select-sub">Select</button></div>
                  </div>
              </div>
              <div class="col-md-4">
                  <div class="card-subject" data-sub="vocab">
                      <i class="bi bi-book"></i>
                      <h5>Vocabulary</h5>
                      <div class="mt-2"><button type="button" class="select-sub">Select</button></div>
                  </div>
              </div>
          </div>

          <div class="mb-3 mt-4">
              <label for="difficulty" class="form-label">Select Difficulty</label>
              <select id="difficulty" class="form-select">
                  <option value="1">Low</option>
                  <option value="2">Medium</option>
                  <option value="3">High</option>
              </select>
          </div>

          <div class="mt-4 text-center">
              <button id="openQuiz" class="btn" disabled>🚀 Start Quiz</button>
          </div>
      </div>
  </div>

  <script>
  // Track selected subject
  let selectedSubject = null;
  document.querySelectorAll('.select-sub').forEach(btn => {
      btn.addEventListener('click', e => {
          document.querySelectorAll('.card-subject').forEach(c => c.classList.remove('selected-card'));
          const card = e.currentTarget.closest('.card-subject');
          card.classList.add('selected-card');
          selectedSubject = card.dataset.sub;
          const startBtn = document.getElementById('openQuiz');
          startBtn.disabled = false;
          startBtn.innerText = '🚀 Start Quiz: ' + card.querySelector('h5').innerText;
      });
  });

  // Redirect via POST to quiz.php
  document.getElementById('openQuiz').addEventListener('click', () => {
      if (!selectedSubject) return alert('Please select a subject!');
      const difficulty = document.getElementById('difficulty').value;
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'quiz.php';
      const subInput = document.createElement('input');
      subInput.type = 'hidden'; subInput.name = 'subject'; subInput.value = selectedSubject;
      const diffInput = document.createElement('input');
      diffInput.type = 'hidden'; diffInput.name = 'difficulty'; diffInput.value = difficulty;
      form.appendChild(subInput);
      form.appendChild(diffInput);
      document.body.appendChild(form);
      form.submit();
  });
  </script>

  </body>
  </html>
