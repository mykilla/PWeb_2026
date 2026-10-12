
const students = [
    {
        name: "Andi Pratama",
        nim: "1001"
    },
    {
        name: "Anisa Putri",
        nim: "1002"
    },
    {
        name: "Ananda Rizky",
        nim: "1003"
    },
    {
        name: "Budi Santoso",
        nim: "1004"
    },
    {
        name: "Citra Maharani",
        nim: "1005"
    }
];

const nameInput = document.getElementById("name");
const nimInput = document.getElementById("nim");
const suggestions = document.getElementById("suggestions");
const form = document.getElementById("registrationForm");

nameInput.addEventListener("input", function () {
    const keyword = nameInput.value.trim().toLowerCase();

    suggestions.innerHTML = "";

    if (keyword === "") {
        return;
    }

    const matches = students.filter(function (student) {
        return student.name.toLowerCase().includes(keyword);
    });

    matches.forEach(function (student) {
        const item = document.createElement("div");

        item.className = "suggestion-item";
        item.textContent = student.name;

        item.addEventListener("click", function () {
            nameInput.value = student.name;
            nimInput.value = student.nim;
            suggestions.innerHTML = "";
        });

        suggestions.appendChild(item);
    });
});

form.addEventListener("submit", function (event) {
    event.preventDefault();

    const name = nameInput.value.trim();
    const nim = nimInput.value.trim();
    const course = document.getElementById("course").value.trim();
    const lecturer = document.getElementById("lecturer").value.trim();

    if (!name || !nim || !course || !lecturer) {
        alert("Please complete all fields.");
        return;
    }

    let result = document.getElementById("result");

    if (!result) {
        result = document.createElement("div");
        result.id = "result";
        form.insertAdjacentElement("afterend", result);
    }

    result.textContent =
        "Registration submitted! Student: " + name +
        ", NIM: " + nim +
        ", Course: " + course +
        ", Lecturer: " + lecturer;
});