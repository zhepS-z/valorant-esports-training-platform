<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Valorant Premier Test</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
  <div class="container py-5">
    <h1 class="mb-4">Test Valorant Premier API</h1>
    
    <form action="team.php" method="get" class="mb-4">
      <div class="mb-3">
        <label class="form-label">Team Name</label>
        <input type="text" name="name" class="form-control" placeholder="Example: Team Alpha" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Team Tag</label>
        <input type="text" name="tag" class="form-control" placeholder="Example: ALH" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Region (Optional)</label>
        <input type="text" name="region" class="form-control" placeholder="ap / na / eu ... (leave empty if not sure)">
      </div>
      <button type="submit" class="btn btn-primary">ค้นหาทีม</button>
    </form>
  </div>
</body>
</html>
