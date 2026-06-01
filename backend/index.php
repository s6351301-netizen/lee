<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聊天室</title>
</head>


<body>
 
<h2>美化且有測試過的聊天室 Durable ObjectJS語法</h2>
<script>
export class ChatRoom {
  constructor(state, env) {
    this.state = state;
    this.env = env;
    this.sessions = [];
  }

  // 處理 WebSocket 連線
  async fetch(request) {
    if (request.headers.get("Upgrade") !== "websocket") {
      return new Response("Expected WebSocket", { status: 400 });
    }

    const [client, server] = Object.values(new WebSocketPair());
    await this.handleSession(server);
    return new Response(null, { status: 101, webSocket: client });
  }

  async handleSession(ws) {
    ws.accept();
    this.sessions.push(ws);

    ws.addEventListener("message", (event) => {
      const message = event.data;
      this.broadcast(message);
    });

    ws.addEventListener("close", () => {
      this.sessions = this.sessions.filter(s => s !== ws);
    });
  }

  broadcast(message) {
    for (const session of this.sessions) {
      try {
        session.send(message);
      } catch (err) {
        console.error("Send failed:", err);
      }
    }
  }
}
</script>
</body>
</html>