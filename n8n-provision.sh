#!/bin/sh

# Ensure n8n is in the PATH
export PATH=$PATH:/usr/local/bin

echo "Starting automated import..."

echo "Importing Ollama credentials..."
/usr/local/bin/n8n import:credentials --input=/flow/ollama-credential.json

echo "Importing example flow..."
/usr/local/bin/n8n import:workflow --input=/flow/example-flow.json

# Activate the webhook workflow so it can be triggered via API
echo "Activating workflow..."
/usr/local/bin/n8n update:workflow --id=webhook-test-flow --active=true || echo "Workflow activation step skipped."

echo "n8n Provisioning complete!"
echo "NOTE: If n8n was already running, you may need to run './vendor/bin/sail restart n8n' to pick up the active status."
