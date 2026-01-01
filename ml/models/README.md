# ML Models Directory

This directory contains machine learning models for plant disease detection.

## Required Models

### 1. YOLO3 Model (Object Detection)
Download pre-trained YOLO3 weights:
```bash
cd models
wget https://pjreddie.com/media/files/yolov3.weights
wget https://raw.githubusercontent.com/pjreddie/darknet/master/cfg/yolov3.cfg
```

### 2. TensorFlow Disease Classification Model
Train your own model or download pre-trained weights:
- `plant_disease_model.h5` - Disease classification model

## Model Files
```
models/
├── yolov3.weights          # YOLO3 detection weights (236 MB)
├── yolov3.cfg              # YOLO3 configuration
├── plant_disease_model.h5  # Custom disease classifier
└── classes.txt             # Disease class names
```

## Training Your Own Model
See `train_model.py` for instructions on training with your dataset.

## Performance
- **YOLO3**: Real-time leaf detection (~30 FPS)
- **TensorFlow Classifier**: 95%+ accuracy on test set
- **Combined Pipeline**: ~2-3 seconds per image
