// frontend/lib/auth.ts
import axios, { AxiosRequestConfig } from "axios";

const API_BASE_URL = "http://127.0.0.1:8000/api";
const TOKEN_KEY = "token";

class AuthService {
  private static get token(): string | null {
    if (typeof window !== "undefined") {
      return localStorage.getItem(TOKEN_KEY);
    }
    return null;
  }

  private static api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
      "Content-Type": "application/json",
    },
  });

  // Middleware para injetar token dinâmico no header
  private static getAuthHeaders() {
    const token = this.token;
    return token ? { Authorization: `Bearer ${token}` } : {};
  }

  // Método genérico para requisições protegidas
  public static async request<T = any>(
    endpoint: string,
    options: AxiosRequestConfig = {}
  ): Promise<T> {
    try {
      const response = await this.api({
        url: endpoint,
        method: options.method || "GET",
        data: options.data || null,
        headers: {
          ...this.getAuthHeaders(),
          ...options.headers,
        },
      });

      return response.data;
    } catch (error: any) {
      throw error.response?.data?.message || "Erro na requisição.";
    }
  }

  // Login para donor ou ong
  public static async login(
    form:
      | { don_email: string; don_password: string }
      | { ong_email: string; ong_password: string },
    userType: "donor" | "ong" = "donor"
  ) {
    try {
      const url =
        userType === "donor"
          ? "/auth/donor/login"
          : "/auth/ong/login";

      const response = await this.api.post(url, form);
      const { token } = response.data;

      if (typeof window !== "undefined") {
        localStorage.setItem(TOKEN_KEY, token);
      }

      this.api.defaults.headers.Authorization = `Bearer ${token}`;
      return response.data;
    } catch (error: any) {
      throw error.response?.data?.message || "Erro ao fazer login.";
    }
  }

  // Logout (chama API e remove token local)
  public static async logout(userType: "donor" | "ong" = "donor") {
    const token = this.token;

    if (!token) return;

    try {
      const url =
        userType === "donor"
          ? "/auth/donor/logout"
          : "/auth/ong/logout";

      await this.api.post(url, null, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });
    } catch (error) {
      console.warn("Erro ao chamar logout na API:", error);
    }

    if (typeof window !== "undefined") {
      localStorage.removeItem(TOKEN_KEY);
    }

    delete this.api.defaults.headers.Authorization;
  }

  public static isAuthenticated(): boolean {
    return !!this.token;
  }

  public static async updateDonor(id: string, formData: FormData) {
    try {
      const response = await this.api.post(`/api/donors/${id}`, formData, {
        headers: {
          ...this.getAuthHeaders(),
          "Content-Type": "multipart/form-data",
        },
      });
      return response.data;
    } catch (error: any) {
      throw error.response?.data?.message || "Erro ao atualizar doador.";
    }
  }
}

export default AuthService;
