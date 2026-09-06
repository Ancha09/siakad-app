function setRole(role, el){

    document.getElementById("role").value = role;

    document.querySelectorAll(".role-tab")
        .forEach(btn => btn.classList.remove("active"));

    el.classList.add("active");
import './login';
}