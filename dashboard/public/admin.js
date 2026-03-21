var admin = {
  loading: (show) => {
    var block = document.getElementById("page-loader");
    if (!block) return;
    if (show) {
      block.classList.add("active");
    } else {
      block.classList.remove("active");
    }
  },

  sidebar: () => {
    var sidebar = document.getElementById("page-sidebar");
    if (!sidebar) return;
    sidebar.classList.toggle("active");
  },

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
        // mark logout state so live dashboard polling won't show fake errors
        window.__loggingOut = true;
        try {
          sessionStorage.setItem("papertrade_logging_out", "1");
        } catch (e) {}

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
          },
          fail: (message) => {
            window.__loggingOut = false;
            try {
              sessionStorage.removeItem("papertrade_logging_out");
            } catch (e) {}

            Swal.fire({
              title: "Logout Failed",
              text: message || "Unable to log out.",
              icon: "error"
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
        if (typeof opt.fail === "function") {
          opt.fail(err.message || "Unable to connect to the server.");
          return;
        }
        Swal.fire({
          title: "Request Failed",
          text: err.message || "Unable to connect to the server.",
          icon: "error",
          confirmButtonText: "OK"
        });
      });
  }
};