# Server Condition Summary - Ollama Server

**Last Updated:** 2026-04-19 10:48 (Local Time)

## 1. Hardware Specifications
- **CPU:** AMD Ryzen 5 2600 Six-Core Processor
- **Memory:** 31 GiB Total (3.6 GiB used, ~27 GiB available/cache)
- **GPU:** NVIDIA GeForce RTX 3060 (12 GiB VRAM)
- **Disk Space:** 466 GB Total, 353 GB Used (80% usage), 94 GB Available
- **Network Host:** 100.121.116.17 (Tailscale/Private IP)

## 2. Software Environment
- **OS:** Ubuntu Linux (6.8.0-110-generic)
- **Python:** 3.12.3
- **Docker:** 29.4.0
- **NVIDIA Driver:** 580.126.09 (CUDA 13.0)

## 3. Ollama Status
Ollama is installed and active.

### Installed Models
| Name | ID | Size | Last Modified |
| :--- | :--- | :--- | :--- |
| **deepseek-v2.5:latest** | 409b2dd8a3c4 | 132 GB | 2 hours ago |
| **qwen2.5:32b** | 9f13ba1299af | 19 GB | 4 hours ago |
| **qwen2.5:14b** | 7cdf5a0187d5 | 9.0 GB | 4 hours ago |
| **llama3.2-vision:latest** | 6f2f9757ae97 | 7.8 GB | 4 hours ago |
| **mistral-nemo:latest** | e7e06d107c6c | 7.1 GB | 10 hours ago |
| **gemma2:9b** | ff02c3702f32 | 5.4 GB | 10 hours ago |
| **hermes3:latest** | 4f6b83f30b62 | 4.7 GB | 4 hours ago |
| **llama3.1:latest** | 46e0c10c039e | 4.9 GB | 10 hours ago |
| **aya:latest** | 7ef8c4942023 | 4.8 GB | 10 hours ago |
| **phi3.5:latest** | 61819fb370a3 | 2.2 GB | 10 hours ago |
| **mxbai-embed-large:latest** | 468836162de7 | 669 MB | 10 hours ago |
| **nomic-embed-text:latest** | 0a109f422b47 | 274 MB | 11 hours ago |
| *(and others...)* | | | |

### Current Progress
- Most requested models have completed downloading.
- `deepseek-v2.5` (132 GB) appears to have finished downloading recently (modified 2 hours ago).
- **Warning:** `deepseek-v2.5` is extremely large for the current hardware (12GB VRAM / 32GB RAM). Performance will be significantly degraded as it will rely heavily on System RAM or may fail to load entirely if resources are insufficient.

## 4. Maintenance Notes
- **Disk Usage:** 80% used. Monitor available space if more large models (like DeepSeek) are added.
- **Service Stability:** No models are currently running (`ollama ps` returned empty).
