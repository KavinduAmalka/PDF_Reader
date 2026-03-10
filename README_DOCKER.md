# Docker Deployment Guide

## Prerequisites
- Docker installed on your system
- Docker Compose installed

## Quick Start

### 1. Setup Environment Variables
Copy the example environment file and add your API keys:
```bash
cp .env.example .env
```

Edit `.env` and add your Gemini API key:
```
GEMINI_API_KEY=your_actual_api_key_here
```

### 2. Build and Run with Docker Compose
```bash
docker-compose up -d
```

### 3. Access the Application
Open your browser and navigate to:
```
http://localhost:8080
```

## Docker Commands

### Start the application
```bash
docker-compose up -d
```

### Stop the application
```bash
docker-compose down
```

### View logs
```bash
docker-compose logs -f
```

### Rebuild after code changes
```bash
docker-compose up -d --build
```

### Stop and remove volumes (WARNING: deletes uploaded files)
```bash
docker-compose down -v
```

## Manual Docker Build (without Docker Compose)

### Build the image
```bash
docker build -t pdf-reader:latest .
```

### Run the container
```bash
docker run -d \
  -p 8080:80 \
  -v ./uploads:/var/www/html/uploads \
  -v ./.env:/var/www/html/.env:ro \
  --name pdf-reader-app \
  pdf-reader:latest
```

### Stop the container
```bash
docker stop pdf-reader-app
docker rm pdf-reader-app
```

## Troubleshooting

### Permission Issues
If you encounter permission issues with uploads:
```bash
docker-compose exec pdf-reader chown -R www-data:www-data /var/www/html/uploads
docker-compose exec pdf-reader chmod -R 755 /var/www/html/uploads
```

### View Container Logs
```bash
docker-compose logs pdf-reader
```

### Access Container Shell
```bash
docker-compose exec pdf-reader bash
```

## Production Deployment

For production, consider:
1. Using a reverse proxy (nginx/traefik) for SSL/TLS
2. Setting up proper backup for the uploads directory
3. Using environment-specific .env files
4. Implementing log rotation
5. Setting resource limits in docker-compose.yml

### Example with Resource Limits
Add to `docker-compose.yml` under the service:
```yaml
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 512M
        reservations:
          memory: 256M
```

## Notes
- The application runs on port 8080 by default (can be changed in docker-compose.yml)
- Uploaded files are persisted in the `./uploads` directory
- The `.env` file is mounted as read-only for security
- Container restarts automatically unless stopped manually
