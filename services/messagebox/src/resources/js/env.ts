export enum EnvironmentType {
    Local = 'local',
    Develop = 'develop',
    Test = 'test',
    Staging = 'staging',
    Production = 'production',
}

export interface Environment {
    version: string;
    environment: EnvironmentType;
    lifetime: number;
}

const env: Environment = {
    version: window.config?.version || 'latest',
    environment: window.config?.environment || EnvironmentType.Production,
    lifetime: window.config?.lifetime || 15,
};

export default env;
