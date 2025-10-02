<?php
$conn = mysqli_connect("127.0.0.1", "trade_user", "trade_user", "trade", 3307);
if ($conn) {
  echo "✅ Connected successfully!";
} else {
  echo "❌ Error: " . mysqli_connect_error();
}
// test2
?>
