import { ChatLogPayload, RequirementsPayload, ChatMessage, ServiceType, ClientInfo } from '../types';

/**
 * Sends the full transcript to backend for requirements storage.
 */
export const triggerRequirementsGeneration = async (
  webhookUrl: string, // Legacy arg, ignored or can be repurposed if needed.
  transcript: ChatMessage[],
  sessionId: string,
  serviceType: ServiceType,
  clientInfo: ClientInfo
): Promise<boolean> => {

  // Use local backend API URL directly
  // In production/docker, the nginx or vite proxy might serve this, but here we assume relative path or env var
  // For portfolio demo, hardcoding or relative path is safest.
  const backendUrl = '/api/requirements';
  // Note: Vite proxy should handle /api -> http://localhost:5000/api if running locally with npm run dev
  // If running in docker, the frontend is served on 80 and the backend on 5000, 
  // but usually we want to use a proxy or full URL. 
  // Let's assume the user accesses http://localhost:8080 (frontend).
  // Request to http://localhost:8080/api/requirements won't work unless frontend container has nginx config proxying to backend.
  // The docker-compose has front on 8080:80. The front Dockerfile (which I haven't seen yet) probably serves static files.
  // If it's static files, we can't proxy without Nginx config.
  // So we might need the full URL: http://localhost:5000/api/requirements.
  // BUT: localhost:5000 is for the browser to access.
  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:5000/api/requirements';

  const payload = {
    session_id: sessionId,
    service_type: serviceType,
    messages: transcript,
    client_info: clientInfo
  };

  try {
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      console.error(`Backend submission failed with status: ${response.status}`);
      return false;
    }

    return true;
  } catch (error) {
    console.error("Error triggering requirements submission:", error);
    return false;
  }
};