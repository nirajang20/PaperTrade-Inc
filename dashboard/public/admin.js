var admin = {
	// (A) SHOW/HIDE "NOW LOADING" BLOCK
	loading : (show) => {
    var block = document.getElementById("page-loader");
    if (show) { block.classList.add("active"); }
    else { block.classList.remove("active"); }
	},

  // (B) TOGGLE SIDE BAR
	sidebar : () => {
    document.getElementById("page-sidebar").aList.toggle("active");
	},

  // (C) SIGN OFF
	bye : () => {
    const swalWithBootstrapButtons = Swal.mixin({
  customClass: {
    confirmButton: "btn btn-success",
    cancelButton: "btn btn-danger"
  },
  buttonsStyling: false
});
swalWithBootstrapButtons.fire({
  title: "Are you sure?",
  text: "You need to login again!",
  icon: "warning",
  showCancelButton: true,
  confirmButtonText: "Yes, logout!",
  cancelButtonText: "No, cancel!",
  reverseButtons: true
}).then((result) => {
  if (result.isConfirmed) {
    swalWithBootstrapButtons.fire({
      title: "Logged out!",
      text: "You have been logged out.",
      icon: "success"
    }).then(() => {
      admin.ajax({
          url : url.host + "ajax-session.php",
          data : { req : "out" },
          ok : () => { location.reload(); }
        });
    });
  } else if (
    /* Read more about handling dismissals below */
    result.dismiss === Swal.DismissReason.cancel
  ) {
    swalWithBootstrapButtons.fire({
      title: "Cancelled",
      text: "You are still logged in :)",
      icon: "error"
    });
  }
});
  },

  // (D) AJAX FETCH
  //  url : target url
  //  data : data to send
  //  ok : function to run on server "OK"
  ajax : (opt) => {
    // (D1) FORM DATA
    let data = new FormData();
    for (let [k, v] of Object.entries(opt.data)) { data.append(k, v); }

    // (D2) FETCH
    fetch(opt.url, { method:"POST", body:data })
    .then((res) => {
      if (res.status != 200) {
        alert(`Server ${res.status} error`);
        console.error(res);
      } else { return res.text(); }
    })
    .then((txt) => {
      if (txt != "OK") { 
        Swal.fire({
          title: txt,
          text: "Please check your login details and try again.",
          icon: "error",
          iconColor: "#e74c3c",
          confirmButtonText: "Try Again",
          confirmButtonColor: "#e74c3c",
          showClass: {
            popup: "animate__animated animate__shakeX"
          },
          hideClass: {
            popup: "animate__animated animate__fadeOutUp"
          }
        });
    }
      else { opt.ok(); }
    })
    .catch((err) => { console.error(err); });
  }
}

