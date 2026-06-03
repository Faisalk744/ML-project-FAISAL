# Real-Time Bus Detection and Occupancy Detection System

## Overview
This project is a Machine Learning and Computer Vision based system designed to detect buses in real time and estimate passenger occupancy. The system uses object detection techniques to identify buses and count passengers from live video streams or recorded footage.

The main objective is to help public transportation authorities monitor bus occupancy levels, improve fleet management, and enhance passenger safety.

---

## Features

- Real-time bus detection using deep learning models
- Passenger detection and counting
- Occupancy percentage calculation
- Live video stream processing
- Automatic alert generation when occupancy exceeds threshold
- Visualization of detected buses and passengers with bounding boxes

---

## Technologies Used

- Python
- OpenCV
- YOLO (You Only Look Once)
- NumPy
- Pandas
- TensorFlow / PyTorch
- Matplotlib

---

## System Architecture

1. Video Input (CCTV Camera / Live Feed)
2. Bus Detection Module
3. Passenger Detection Module
4. Occupancy Calculation
5. Result Visualization
6. Monitoring Dashboard

---

## Dataset

The model is trained using:
- Bus image dataset
- Passenger detection dataset
- Custom annotated images and videos

### Data Preprocessing
- Image resizing
- Data augmentation
- Annotation labeling
- Dataset splitting (Train, Validation, Test)

---

## Working Process

1. Capture video frames from camera.
2. Detect buses using the trained object detection model.
3. Detect and count passengers inside the bus.
4. Calculate occupancy using:

Occupancy (%) = (Detected Passengers / Bus Capacity) × 100

5. Display occupancy status in real time.
6. Generate alerts if occupancy exceeds predefined limits.

---

## Installation

### Clone Repository

```bash
git clone https://github.com/your-username/bus-occupancy-detection.git
cd bus-occupancy-detection
```

### Install Dependencies

```bash
pip install -r requirements.txt
```

### Run Project

```bash
python main.py
```

---

## Project Structure

```text
├── dataset/
├── models/
├── trained_weights/
├── videos/
├── outputs/
├── main.py
├── occupancy_detector.py
├── bus_detector.py
├── requirements.txt
└── README.md
```

---

## Results

- Accurate bus detection in real-time video streams.
- Efficient passenger counting.
- Real-time occupancy monitoring.
- Improved transportation analytics and decision-making.

---

## Future Enhancements

- Integration with IoT devices.
- Cloud-based monitoring dashboard.
- Mobile application support.
- Multi-camera tracking system.
- Advanced analytics and reporting.

---

## Applications

- Smart Transportation Systems
- Public Bus Monitoring
- Fleet Management
- Passenger Safety Monitoring
- Smart City Projects

---

## Authors

Developed as a Machine Learning Project for Real-Time Bus Detection and Occupancy Monitoring.
