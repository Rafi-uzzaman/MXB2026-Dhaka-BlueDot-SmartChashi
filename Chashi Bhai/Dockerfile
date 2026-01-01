# Use Python 3.11 slim image
FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Copy requirements first for better caching
COPY requirements.txt .

# Install dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application files
COPY . .

# Expose port (Railway will set this via $PORT)
EXPOSE 8080

# Start the application
CMD uvicorn backend:app --host 0.0.0.0 --port ${PORT:-8080}
