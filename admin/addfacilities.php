<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Facility</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: url('background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-family: 'Poppins', sans-serif;
        }

nav {
background: #bfd9dc;
padding: 10px 40px;
border-bottom-left-radius: 25px;
border-bottom-right-radius: 25px;
display: flex;
justify-content: space-between;
align-items: center;
position: sticky;
top: 0;
z-index: 1000;
box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}


.nav-logo img {
height: 65px;
}


.nav-link {
color: #071239ff;
font-weight: 600;
padding: 8px 18px;
border-radius: 12px;
transition: all 0.3s ease;
}


.nav-link.selected {
box-shadow: 0 0 10px #2ed573;
}


.nav-profile img {
width: 45px;
height: 45px;
}

        .main-box {
            background: #bfd9dc;
            border-radius: 25px;
            padding: 30px;
            color: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            max-width: 700px;
            margin: 40px auto;
        }

    h1 {
    font-weight: 900;
    text-align: center;
    margin-bottom: 20px;
    font-size: 40px;
    letter-spacing: -1px;
    background: linear-gradient(90deg, #ffffff, #d9d9d9, #f2f2f2);
    -webkit-background-clip: text;
    color: #071239ff
}

        .form-label { font-weight: 600; font-size: 14px;  color: #071239ff; }
        .form-control, .form-select, textarea {
            border-radius: 12px;
            padding: 4px 10px;
            font-size: 14px;
            margin-bottom: 4px;
            color: #071239ff
        }

        .upload-box {
            background: white;
            width: 240px;
            height: 240px;
            border-radius: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            color: #071239ff;
            font-size: 14px;
            border: 2px dashed #ccc;
            cursor: pointer;
            margin: auto;
        }

        .btn-reset {
            background: #c62828;
            color: white;
            padding: 6px 22px;
            border-radius: 10px;
        }

        .btn-submit {
            background: #1e40af;
            color: white;
            padding: 6px 22px;
            border-radius: 10px;
        }

        .status-active { background:#2e7d32; color:white; padding:4px 10px; border-radius:8px; }
        .status-maintenance { background:#f9a825; color:black; padding:4px 10px; border-radius:8px; }
        .status-archived { background:#b71c1c; color:white; padding:4px 10px; border-radius:8px; }

       
    </style>
</head>
<body>

<nav class="d-flex justify-content-between align-items-center px-4 py-2">
    <div class="nav-logo d-flex align-items-center gap-3">
        <img src="ukmlogo.png">
        <img src="pusatsukan.png">
    </div>

    <div class="d-flex align-items-center gap-4">
        <a class="nav-link active" href="#">Facility</a>
        <a class="nav-link" href="#">Booking</a>
        <a class="nav-link" href="#">Report</a>

        <div class="d-flex align-items-center gap-1">
            <img src="user.png" class="rounded-circle" style="width:45px; height:45px;">
            <span class="fw-bold" styke="color: #071239ff;">A203914</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="main-box position-relative">
           
        <h1>ADD NEW FACILITY</h1>

        <form>
            <div class="row g-3 justify-content-center">
                <div class="col-md-8">
                    <div class="mb-2">
                        <label class="form-label">Facility ID</label>
                        <input type="text" class="form-control" placeholder="Auto generated" readonly>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Facility Name</label>
                        <input type="text" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Facility Type</label>
                        <input type="text" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Capacity</label>
                        <input type="number" class="form-control">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" onchange="this.className='form-select '+this.options[this.selectedIndex].dataset.class">
                            <option value="active" data-class="status-active">Active</option>
                            <option value="maintenance" data-class="status-maintenance">Maintenance</option>
                            <option value="archived" data-class="status-archived">Archived</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3"></textarea>
                    </div>

                    <div class="text-center mb-2">
                        <label class="form-label d-block">Upload Photo</label>
                        <div class="upload-box">
                            <div style="font-size:40px;"></div>
                            <span>Click to upload</span>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="reset" class="btn btn-reset me-2">Reset</button>
                        <button type="submit" class="btn btn-submit">Submit</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

</body>
</html>