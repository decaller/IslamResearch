from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List
from tasks.reranker import semantic_rerank
import os

app = FastAPI(title="Semantic Re-ranker API")

class RerankRequest(BaseModel):
    query: str
    documents: List[str]
    window_size: int = 8
    stride: int = 1

@app.post("/rerank")
async def rerank(request: RerankRequest):
    try:
        result = semantic_rerank(
            query=request.query,
            documents=request.documents,
            window_size=request.window_size,
            stride=request.stride
        )
        
        if result is None:
            raise HTTPException(status_code=404, detail="No matching chunks found.")
            
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    port = int(os.environ.get("RERANK_PORT", 8001))
    uvicorn.run(app, host="0.0.0.0", port=port)
