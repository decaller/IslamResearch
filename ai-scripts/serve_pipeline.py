import os
import sys

# Ensure unbuffered output for Docker logs
os.environ["PYTHONUNBUFFERED"] = "1"

print("Starting AI Pipeline deployment runner...", flush=True)

try:
    print("Importing pipeline flow...", flush=True)
    from main_pipeline import process_batch
    print("Pipeline flow imported successfully.", flush=True)
except Exception as e:
    print(f"Error importing main_pipeline: {e}", flush=True)
    sys.exit(1)

if __name__ == "__main__":
    print("Serving Islamic Text Ingestion flow on Prefect v3...", flush=True)
    try:
        process_batch.serve(
            name="ingestion-deployment",
            tags=["islamic-text"],
        )
    except Exception as e:
        print(f"Error during flow.serve(): {e}", flush=True)
        sys.exit(1)
