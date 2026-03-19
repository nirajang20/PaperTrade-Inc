var admin = {
  // (A) SHOW/HIDE "NOW LOADING" BLOCK
  loading: (show) => {
    var block = document.getElementById("page-loader");
    if (!block) return;
    if (show) {
      block.classList.add("active");
    } else {
      block.classList.remove("active");
    }
  },

  // (B) TOGGLE SIDE BAR
  sidebar: () => {
    var sidebar = document.getElementById("page-sidebar");
    if (!sidebar) return;
    sidebar.classList.toggle("active");
  },

  // (C) SIGN OFF
  bye: () => {
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
        admin.ajax({
          url: url.host + "auth/ajax/session.php",
          data: { req: "out" },
          ok: () => {
            Swal.fire({
              title: "Logged out!",
              text: "You have been logged out.",
              icon: "success"
            }).then(() => {
              window.location.href = url.host + "auth/login.php";
            });
          }
        });
      } else if (result.dismiss === Swal.DismissReason.cancel) {
        swalWithBootstrapButtons.fire({
          title: "Cancelled",
          text: "You are still logged in :)",
          icon: "error"
        });
      }
    });
  },

  // (D) AJAX FETCH
  // opt = {
  //   url: target url,
  //   data: object,
  //   ok: function(txt){},
  //   fail: function(txt){}
  // }
  ajax: (opt) => {
    let data = new FormData();
    for (let [k, v] of Object.entries(opt.data || {})) {
      data.append(k, v);
    }

    fetch(opt.url, { method: "POST", body: data })
      .then((res) => {
        if (!res.ok) {
          throw new Error(`Server ${res.status} error`);
        }
        return res.text();
      })
      .then((txt) => {
        txt = (txt || "").trim();

        if (txt === "OK") {
          if (typeof opt.ok === "function") {
            opt.ok(txt);
          }
        } else {
          if (typeof opt.fail === "function") {
            opt.fail(txt);
            return;
          }

          Swal.fire({
            title: "Error",
            text: txt || "Something went wrong.",
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
      })
      .catch((err) => {
        console.error(err);
        Swal.fire({
          title: "Request Failed",
          text: err.message || "Unable to connect to the server.",
          icon: "error",
          confirmButtonText: "OK"
        });
      });
  }
};