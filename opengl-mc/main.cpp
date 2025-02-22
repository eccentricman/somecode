#include "Angel.h"
#include "TriMesh.h"
#include "Camera.h"
#include "MeshPainter.h"
#include <vector>
#include <string>
int WIDTH = 600;
int HEIGHT = 600;
#define world_size 32
int mainWindow;
Camera* camera = new Camera();
Light* light = new Light();
TriMesh* sun = new TriMesh();
TriMesh* cross = new TriMesh();
MeshPainter *painter = new MeshPainter();
glm::vec3 sunTranslation =glm::vec3(2.5, 10.0f, 2.5f);
// 这个用来回收和删除我们创建的物体对象
std::vector<TriMesh *> meshList;
bool is_exis[world_size][world_size/2][world_size] = { false };
float move_step_size = 0.2;
class MatrixStack {
	int		_index;
	int		_size;
	glm::mat4* _matrices;
public:
	MatrixStack(int numMatrices = 100) :_index(0), _size(numMatrices)
	{
		_matrices = new glm::mat4[numMatrices];
	}
	~MatrixStack()
	{
		delete[]_matrices;
	}
	void push(const glm::mat4& m) {
		assert(_index + 1 < _size);
		_matrices[_index++] = m;
	}
	glm::mat4& pop() {
		assert(_index - 1 >= 0);
		_index--;
		return _matrices[_index];
	}
};
struct Robot
{
	// 关节大小
	float TORSO_HEIGHT = 0.675;
	float TORSO_WIDTH = 0.225;
	float TORSO_LENGTH = 0.45;

	float UPPER_ARM_HEIGHT = 0.3375;
	float LOWER_ARM_HEIGHT = 0.3375;
	float UPPER_ARM_WIDTH = 0.225;
	float LOWER_ARM_WIDTH = 0.225;
	float UPPER_ARM_LENGTH = 0.225;
	float LOWER_ARM_LENGTH = 0.225;

	float UPPER_LEG_HEIGHT = 0.3375;
	float LOWER_LEG_HEIGHT = 0.3375;
	float UPPER_LEG_WIDTH = 0.225;
	float LOWER_LEG_WIDTH = 0.225;
	float UPPER_LEG_LENGTH = 0.225;
	float LOWER_LEG_LENGTH = 0.225;

	float HEAD_HEIGHT = 0.45;
	float HEAD_WIDTH = 0.45;
	float HEAD_LENGTH = 0.45;
	float angle = 0.0f; // 初始方向角度，面向z轴
	float up_angel= 0.0f;
	float radius=4.0f;//互动半径
	float radius_3 = 4.0f;//第三人称半径
	float v_up = 0.0f; // 初始速度，可以根据需要调整
	float g = 9.8f; // 重力加速度
	float current_h=0.0f;
	float collision_size = 0.3;
	glm::vec4 up;
	glm::vec4 robot_location = glm::vec4(2, 4, 2,1.0f);
	glm::vec4 last_location = glm::vec4(0.0f, 0.0f, -0.1f, 1.0f);
	// 更新速度向量
	void updateVelocity() {
		// 更新水平速度
		velocity.x = glm::cos(glm::radians(up_angel)) * glm::sin(glm::radians(angle));
		velocity.z = glm::cos(glm::radians(up_angel)) * glm::cos(glm::radians(angle));

		// 更新垂直速度
		velocity.y = glm::sin(glm::radians(up_angel));

		// 更新机器人朝向
		theta[Torso] = angle;
	}

	// 速度属性
	glm::vec3 velocity;
	float speed = 2.0f; // 速度大小
	// 关节角和菜单选项值
	enum {
		Torso,			// 躯干
		Head,			// 头部
		RightUpperArm,	// 右大臂
		RightLowerArm,	// 右小臂
		LeftUpperArm,	// 左大臂
		LeftLowerArm,	// 左小臂
		RightUpperLeg,	// 右大腿
		RightLowerLeg,	// 右小腿
		LeftUpperLeg,	// 左大腿
		LeftLowerLeg,	// 左小腿
	};
	// 关节角大小
	GLfloat theta[10] = {
		0.0,    // Torso
		0.0,    // Head
		0.0,    // RightUpperArm
		0.0,    // RightLowerArm
		0.0,    // LeftUpperArm
		0.0,    // LeftLowerArm
		0.0,    // RightUpperLeg
		0.0,    // RightLowerLeg
		0.0,    // LeftUpperLeg
		0.0     // LeftLowerLeg
	};
};
enum CubeType
{
	GRASS_BLOCK,
	Log,
	diamond,
	smooth_stone,
	stone
};
std::string path[5] = {
	"./assets/mc-grass.jpg",
	"./assets/log.jpg",
	"./assets/diamond.jpg",
	"./assets/smooth_stone.jpg",
	"./assets/stone.jpg"
};
CubeType current_type = GRASS_BLOCK;
Robot robot;
// 机器人
TriMesh* Torso = new TriMesh();
TriMesh* Head = new TriMesh();
TriMesh* RightUpperArm = new TriMesh();
TriMesh* RightLowerArm = new TriMesh();
TriMesh* LeftUpperArm = new TriMesh();
TriMesh* LeftLowerArm = new TriMesh();
TriMesh* RightUpperLeg = new TriMesh();
TriMesh* RightLowerLeg = new TriMesh();
TriMesh* LeftUpperLeg = new TriMesh();
TriMesh* LeftLowerLeg = new TriMesh();
TriMesh* hand_block = new TriMesh();
std::string vshader = "shaders/vshader.glsl";
std::string fshader = "shaders/fshader.glsl";
void deal_pointers(int &x,  int &y, int &z) {
	if (x > world_size) x = world_size;
	if (y > world_size/2) y = world_size/2;
	if (z > world_size) z = world_size;
	if (x < 0)x = 0;
	if (y < 0)y = 0;
	if (z < 0)z = 0;
}
void drawblock(CubeType type,int x, int y, int z) {
	// 创建正方形
	if (!is_exis[x][y][z]) {
		TriMesh* square = new TriMesh();
		std::string texturePath;
		std::string name="block";
		texturePath = path[type];
		// 设置正方形的变换属性
		square->generateBlock();
		square->setTranslation(glm::vec3(x , -0.51f + y, z ));
		square->setRotation(glm::vec3(-90.0, 0.0, 0.0));
		square->setModelMatrix();
		square->setxyz(x, y, z);
		square->name = name;
		// 将正方形添加到Painter中
		painter->addMesh(square, texturePath, vshader, fshader);
		meshList.push_back(square);
		is_exis[x][y][z] = true;
	}
}
void Destoryblock(int x, int y, int z) {
	// 将世界坐标转换为整数索引
	// 检查该位置是否有方块存在
	if (is_exis[x][y][z]) {
		// 从Painter中移除对应的方块
		for (auto it = meshList.begin(); it != meshList.end(); ++it) {
			TriMesh* square = *it;
			// 假设每个TriMesh对象都有一个name属性，用来标识方块类型
			if (square->x == x &&square->y == y &&square->z == z) {
				// 从Painter中移除
				painter->removeMesh(square);
				// 更新is_exis数组
				is_exis[x][y][z] = false;
				meshList.erase(it);
				break; // 找到并移除后跳出循环
			}
		}
	}
}


	void init()
	{
		
		hand_block->generateBlock();
		hand_block->name = "HandBlock";
		painter->addMesh(hand_block, path[current_type], vshader, fshader);
		// 设置光源位置
		light->setTranslation(sunTranslation);
		light->setAmbient(glm::vec4(1.0, 1.0, 1.0, 1.0)); // 环境光
		light->setDiffuse(glm::vec4(0.0, 0.0, 0.0, 1.0)); // 漫反射
		light->setSpecular(glm::vec4(1.0, 1.0, 1.0, 1.0)); // 镜面反射
		light->setAttenuation(1.0, 0.045, 0.0075); // 衰减系数
		// 生成太阳并贴图
		sun->generateBall(10, 0.1);
		// 设置太阳的变换属性
		sun->setTranslation(sunTranslation);  // 平移
		sun->setRotation(glm::vec3(0.0, 0.0, 0.0));     // 旋转
		sun->setScale(glm::vec3(10, 10, 10));         // 缩放
		// 设置太阳的材质属性
		sun->setAmbient(glm::vec4(100.0, 100.0, 100.0, 1.0));  // 环境光
		sun->setDiffuse(glm::vec4(80, 80, 80, 1.0));  // 漫反射
		sun->setSpecular(glm::vec4(2, 2, 2, 1.0)); // 镜面反射
		sun->setShininess(100.0);                           // 高光系数
		sun->setModelMatrix();
		sun->name = "sun";

		cross->generateBall(10, 0.1);
		cross->setScale(glm::vec3(0.02, 0.03, 0.03));         // 缩放
		cross->setAmbient(glm::vec4(100.0, 100.0, 100.0, 1.0));  // 环境光
		cross->setDiffuse(glm::vec4(100.0, 100.0, 100.0, 1.0));  // 漫反射
		cross->setSpecular(glm::vec4(2, 2, 2, 1.0)); // 镜面反射
		cross->setShininess(100.0);   
		cross->setModelMatrix();
		cross->name = "cross";
		// 高光系数
		painter->addMesh(cross, "./assets/Sun.jpg", vshader, fshader); 	// 指定纹理与着色器

		// 加到painter中
		painter->addMesh(sun, "./assets/Sun.jpg", vshader, fshader); 	// 指定纹理与着色器
		
		const int flatland_height = 2;

		// 生成平地
		for (int y = 0; y < flatland_height; ++y) {
			for (int x = 0; x < world_size; ++x) {
				for (int z = 0; z < world_size; ++z) {
					// 底层使用石头
					if (y <flatland_height-2) {
						drawblock(stone, x, y, z); // 假设STONE_BLOCK是一个常量
					}
					else {
						drawblock(GRASS_BLOCK, x, y, z); // 假设GRASS_BLOCK是一个常量
					}
				}
			}
		}
		// 生成中间的山，高度降低
		for (int y = flatland_height; y < world_size / 4 ; ++y) {
			for (int x = 0; x < world_size; ++x) {
				for (int z = 0; z < world_size; ++z) {
					// 计算到中心的距离
					int dist = (int)sqrt((x - world_size / 2) * (x - world_size / 2) + (z - world_size / 2) * (z - world_size / 2));
					// 根据距离决定是否放置方块
					if (dist < world_size / 4 - (y - flatland_height)) {
						drawblock(GRASS_BLOCK, x, y, z); // 假设GRASS_BLOCK是一个常量
					}
				}
			}
		}
		// 生成机器人并贴图
		Torso->generateBlock();
		Torso->name = "Torso";
		Head->generateBlock();
		Head->name = "Head";
		RightUpperArm->generateBlock();
		RightUpperArm->name = "RightUpperArm";
		LeftUpperArm->generateBlock();
		LeftUpperArm->name = "LeftUpperArm";
		RightUpperLeg->generateBlock();
		RightUpperLeg->name = "RightUpperLeg";
		LeftUpperLeg->generateBlock();
		LeftUpperLeg->name = "LeftUpperLeg";
		RightLowerArm->generateBlock();
		RightLowerArm->name = "RightLowerArm";
		LeftLowerArm->generateBlock();
		LeftLowerArm->name = "LeftLowerArm";
		RightLowerLeg->generateBlock();
		RightLowerLeg->name = "RightLowerLeg";
		LeftLowerLeg->generateBlock();
		LeftLowerLeg->name = "LeftLowerLeg";
	

		// 将机器身体添加到Painter中
		painter->addMesh(Torso, path[2], vshader, fshader);
		painter->addMesh(Head, path[2], vshader, fshader);
		painter->addMesh(LeftUpperArm, path[2], vshader, fshader);
		painter->addMesh(LeftLowerArm, path[2], vshader, fshader);
		painter->addMesh(RightUpperArm, path[2], vshader, fshader);
		painter->addMesh(RightLowerArm, path[2], vshader, fshader);
		painter->addMesh(LeftUpperLeg, path[2], vshader, fshader);
		painter->addMesh(LeftLowerLeg, path[2], vshader, fshader);
		painter->addMesh(RightUpperLeg, path[2], vshader, fshader);
		painter->addMesh(RightLowerLeg, path[2], vshader, fshader);

		
		glClearColor(0.2f, 0.4f, 0.8f, 0.15f);
	}

void updateCamera()
{
	camera->up = glm::vec4(0.0, 1.0, 0.0, 0.0);
	camera->eye = robot.robot_location+ glm::vec4(( - robot.radius_3 ) * robot.velocity.x, 1.5 + (-robot.radius_3) * robot.velocity.y, (-robot.radius_3 ) * robot.velocity.z, 1.0);
	float at_x = robot.radius * robot.velocity.x+ camera->eye.x+ robot.radius_3 * robot.velocity.x;
	float at_y = robot.radius * robot.velocity.y+ camera->eye.y+ robot.radius_3 * robot.velocity.y;
	float at_z = robot.radius * robot.velocity.z + camera->eye.z+ robot.radius_3 * robot.velocity.z;
	camera->at = glm::vec4(at_x, at_y, at_z, 1.0);
	cross->setTranslation(glm::vec3(camera->eye.x + robot.velocity.x, camera->eye.y +  robot.velocity.y, camera->eye.z + robot.velocity.z));
	cross->setModelMatrix();
	sun->setModelMatrix();
}
//得到坐标下整数xyz
glm::vec3 get_xyz(glm::vec3 points) {
	int x = int(points.x + 0.5);
	int y = int(points.y+ 0.5);
	int z = int(points.z + 0.5);
	return glm::vec3(x, y, z);
}
//得到朝向的没有方块位置
glm::vec3 getEmptySpace_xyz() {
	glm::vec4 points = camera->at;
	//从at往eye方向迭代，直到没有方块
	while (glm::length(points-camera->eye)- robot.radius_3 >1) {
		glm::vec3 xyz = get_xyz(points);
	
		int x = xyz.x, y = xyz.y, z = xyz.z;

		if (is_exis[x][y][z]) {
			float point_x = points.x - 0.5 * robot.velocity.x;
			float point_y = points.y - 0.5 * robot.velocity.y;
			float point_z = points.z - 0.5 * robot.velocity.z;
			points = glm::vec4(point_x, point_y, point_z, 1.0);
		}
		else {
			return glm::vec3(x, y, z);
		}
		
	}//如果一直都有方块，返回-1
	return glm::vec3(-1, -1, -1);

}
//得到朝向的有方块位置
glm::vec3 getblock_xyz() {
	glm::vec4 points = camera->at;
	int near_x=-1, near_y=-1, near_z=-1;
	//从at往eye方向迭代，保存最近有方块的地方，没有方块返回-1
	while (glm::length(points - camera->eye)- robot.radius_3  > 1) {
		glm::vec3 xyz = get_xyz(points);
		int x = xyz.x, y = xyz.y, z = xyz.z;
		if (is_exis[x][y][z]) {
			near_x = x; near_y = y; near_z = z;
		}
			float point_x = points.x - 0.5*robot.velocity.x;
			float point_y = points.y - 0.5 * robot.velocity.y;
			float point_z = points.z - 0.5 * robot.velocity.z;
			points = glm::vec4(point_x, point_y, point_z, 1.0);
	}
	return glm::vec3(near_x, near_y, near_z);
}

struct AABB {
	glm::vec3 min; // 最小点
	glm::vec3 max; // 最大点
};
bool is_collision(const AABB& a, const AABB& b) {
	// 检查两个AABB是否在水平方向上相交
	return (a.min.x <= b.max.x && a.max.x >= b.min.x) &&
		(a.min.z <= b.max.z && a.max.z >= b.min.z);
}

void CheckAABBCollision() {
	glm::vec3 xyz = get_xyz(robot.robot_location);
	int x = xyz.x, y = xyz.y, z = xyz.z;

	// 检查x轴方向
	if ((x > 0) && (is_exis[x - 1][y + 1][z] || is_exis[x - 1][y + 2][z])){
		AABB blockAABB = {
					glm::vec3(xyz.x-1 - 0.5f, 0.0f, xyz.z - 0.5f),
					glm::vec3(xyz.x-1 + 0.5f, 0.0f, xyz.z + 0.5f)
		};
		AABB robotAABB = {
			glm::vec3(robot.robot_location.x - robot.collision_size, 0.0f, robot.robot_location.z - robot.collision_size),
			glm::vec3(robot.robot_location.x + robot.collision_size, 0.0f, robot.robot_location.z + robot.collision_size)
		};
		if (is_collision(blockAABB, robotAABB)) {
			robot.robot_location.x = robot.last_location.x;
		}

	}
	if ((x < world_size - 1) && (is_exis[x + 1][y + 1][z] || is_exis[x+1][y + 2][z ]) ){
		AABB blockAABB = {
						glm::vec3(xyz.x + 1 - 0.5f, 0.0f, xyz.z - 0.5f),
						glm::vec3(xyz.x + 1 + 0.5f, 0.0f, xyz.z + 0.5f)
		};
		AABB robotAABB = {
			glm::vec3(robot.robot_location.x - robot.collision_size, 0.0f, robot.robot_location.z - robot.collision_size),
			glm::vec3(robot.robot_location.x + robot.collision_size, 0.0f, robot.robot_location.z + robot.collision_size)
		};
		if (is_collision(blockAABB, robotAABB)) {
			robot.robot_location.x = robot.last_location.x;
		}
	}

	// 检查z轴方向
	if ((z > 0) && (is_exis[x][y + 1][z - 1]|| is_exis[x][y + 2][z - 1])) {
		AABB blockAABB = {
					glm::vec3(xyz.x  - 0.5f, 0.0f, xyz.z-1 - 0.5f),
					glm::vec3(xyz.x  + 0.5f, 0.0f, xyz.z-1 + 0.5f)
		};
		AABB robotAABB = {
			glm::vec3(robot.robot_location.x - robot.collision_size, 0.0f, robot.robot_location.z - robot.collision_size),
			glm::vec3(robot.robot_location.x + robot.collision_size, 0.0f, robot.robot_location.z + robot.collision_size)
		};
		if (is_collision(blockAABB, robotAABB)) {
			robot.robot_location.z = robot.last_location.z;
		}
	}
	if ((z < world_size - 1) && (is_exis[x][y + 1][z + 1] || is_exis[x][y + 2][z +1]) ){
		AABB blockAABB = {
					glm::vec3(xyz.x - 0.5f, 0.0f, xyz.z + 1 - 0.5f),
					glm::vec3(xyz.x + 0.5f, 0.0f, xyz.z + 1 + 0.5f)
		};
		AABB robotAABB = {
			glm::vec3(robot.robot_location.x - robot.collision_size, 0.0f, robot.robot_location.z - robot.collision_size),
			glm::vec3(robot.robot_location.x + robot.collision_size, 0.0f, robot.robot_location.z + robot.collision_size)
		};
		if (is_collision(blockAABB, robotAABB)) {
			robot.robot_location.z = robot.last_location.z;
		}
	}

}
void display()
{
	glClear(GL_COLOR_BUFFER_BIT | GL_DEPTH_BUFFER_BIT);
	robot.updateVelocity();
	updateCamera();

	// 保持变换矩阵的栈
	MatrixStack mstack;
	// 物体的变换矩阵
	glm::mat4 modelMatrix = glm::mat4(1.0);
	// 机器人身体
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0, 0.5 * robot.TORSO_HEIGHT + robot.UPPER_LEG_HEIGHT + robot.LOWER_LEG_HEIGHT, 0.0));
	modelMatrix = glm::translate(modelMatrix, glm::vec3(robot.robot_location.x, robot.robot_location.y, robot.robot_location.z));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.Torso]), glm::vec3(0.0, 1.0, 0.0));
	mstack.push(modelMatrix);
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.TORSO_LENGTH, robot.TORSO_HEIGHT, robot.TORSO_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	Torso->setModelMatrix(modelMatrix);
	
	// 机器人头
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0, 0.5 * robot.TORSO_HEIGHT + 0.5 * robot.HEAD_HEIGHT, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.Head]), glm::vec3(0.0, 1.0, 0.0));
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.HEAD_LENGTH, robot.HEAD_HEIGHT, robot.HEAD_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	Head->setModelMatrix(modelMatrix);
	
	// =========== 左臂 ===========
	// 左大臂
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(-0.5 * robot.TORSO_LENGTH - 0.5 * robot.UPPER_ARM_LENGTH, 0.2, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.LeftUpperArm]), glm::vec3(1.0, 0.0, 0.0));
	mstack.push(modelMatrix);   // 保存大臂变换矩阵
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.LOWER_ARM_LENGTH, robot.LOWER_ARM_HEIGHT, robot.UPPER_ARM_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	LeftUpperArm->setModelMatrix(modelMatrix);
	// @TODO: 左小臂
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0, -robot.UPPER_ARM_HEIGHT, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.LeftLowerArm]), glm::vec3(1.0, 0.0, 0.0));
	mstack.push(modelMatrix);
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.LOWER_ARM_LENGTH, robot.LOWER_ARM_HEIGHT, robot.UPPER_ARM_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	LeftLowerArm->setModelMatrix(modelMatrix);
	//手里的方块
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0f, -robot.LOWER_ARM_HEIGHT/2, robot.LOWER_ARM_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(45.0f), glm::vec3(0.0, 1.0, 0.0));
	mstack.push(modelMatrix);
	modelMatrix = glm::scale(modelMatrix, glm::vec3(0.25, 0.25, 0.25));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	hand_block->setModelMatrix(modelMatrix);
	// =========== 右臂 ===========
	modelMatrix = mstack.pop(); // 恢复方块变换矩阵
	modelMatrix = mstack.pop(); // 恢复小臂变换矩阵
	modelMatrix = mstack.pop(); // 恢复大臂变换矩阵
	modelMatrix = mstack.pop(); // 恢复躯干变换矩阵
	mstack.push(modelMatrix); // 保存躯干变换矩阵
	// @TODO: 右大臂
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.5 * robot.TORSO_LENGTH + 0.5 * robot.UPPER_ARM_LENGTH, 0.2, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.RightUpperArm]), glm::vec3(1.0, 0.0, 0.0));
	mstack.push(modelMatrix);   // 保存大臂变换矩阵
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.LOWER_ARM_LENGTH, robot.LOWER_ARM_HEIGHT, robot.UPPER_ARM_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	RightUpperArm->setModelMatrix(modelMatrix);
	// @TODO: 右小臂
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0, -robot.UPPER_ARM_HEIGHT, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.RightLowerArm]), glm::vec3(1.0, 0.0, 0.0));

	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.LOWER_ARM_LENGTH, robot.LOWER_ARM_HEIGHT, robot.UPPER_ARM_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	RightLowerArm->setModelMatrix(modelMatrix);
	

	// =========== 左腿 ===========

	modelMatrix = mstack.pop(); // 恢复大臂变换矩阵
	modelMatrix = mstack.pop(); // 恢复躯干变换矩阵
	mstack.push(modelMatrix); // 保存躯干变换矩阵
	// @TODO: 左大腿
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.5 * robot.TORSO_LENGTH - 0.5 * robot.LOWER_LEG_LENGTH, -robot.UPPER_LEG_HEIGHT - robot.LOWER_LEG_HEIGHT + 0.2, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.LeftUpperLeg]), glm::vec3(1.0, 0.0, 0.0));
	mstack.push(modelMatrix);   // 保存大腿变换矩阵
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.UPPER_LEG_LENGTH, robot.LOWER_LEG_HEIGHT, robot.LOWER_LEG_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	LeftUpperLeg->setModelMatrix(modelMatrix);
	// @TODO: 左小腿
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0, -robot.UPPER_LEG_HEIGHT, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.LeftLowerLeg]), glm::vec3(1.0, 0.0, 0.0));
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.UPPER_LEG_LENGTH, robot.LOWER_LEG_HEIGHT, robot.LOWER_LEG_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	LeftLowerLeg->setModelMatrix(modelMatrix);

	// =========== 右腿 ===========
	modelMatrix = mstack.pop(); // 恢复躯干变换矩阵
	modelMatrix = mstack.pop(); // 恢复躯干变换矩阵
	mstack.push(modelMatrix); // 保存躯干变换矩阵
	// @TODO: 右大腿
	modelMatrix = glm::translate(modelMatrix, glm::vec3(-0.5 * robot.TORSO_LENGTH + 0.5 * robot.LOWER_LEG_LENGTH, -robot.UPPER_LEG_HEIGHT - robot.LOWER_LEG_HEIGHT + 0.2, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.RightUpperLeg]), glm::vec3(1.0, 0.0, 0.0));
	mstack.push(modelMatrix);   // 保存大腿变换矩阵
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.UPPER_LEG_LENGTH, robot.LOWER_LEG_HEIGHT, robot.LOWER_LEG_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	RightUpperLeg->setModelMatrix(modelMatrix);
	// @TODO: 右小腿
	modelMatrix = mstack.pop();
	mstack.push(modelMatrix);
	modelMatrix = glm::translate(modelMatrix, glm::vec3(0.0, -robot.UPPER_LEG_HEIGHT, 0.0));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(robot.theta[robot.RightLowerLeg]), glm::vec3(1.0, 0.0, 0.0));
	modelMatrix = glm::scale(modelMatrix, glm::vec3(robot.UPPER_LEG_LENGTH, robot.LOWER_LEG_HEIGHT, robot.LOWER_LEG_WIDTH));
	modelMatrix = glm::rotate(modelMatrix, glm::radians(-90.0f), glm::vec3(1.0, 0.0, 0.0));
	RightLowerLeg->setModelMatrix(modelMatrix);
	modelMatrix = mstack.pop();
	modelMatrix = mstack.pop();

	painter->drawMeshes(light, camera);

}



void printHelp()
{
	std::cout << "================================================" << std::endl;
	std::cout << "Use mouse to controll the light position (drag)." << std::endl;
	std::cout << "================================================" << std::endl << std::endl;

	std::cout << "Keyboard Usage" << std::endl;
	std::cout <<
		"[Window]" << std::endl <<
		"ESC:		Exit" << std::endl <<
		"h:		Print help message" << std::endl 
		<<std::endl << "[View Control]" << std::endl
		<< "Mouse Movement:	Control camera angle" << std::endl 
		<< "[Player Movement]" << std::endl
		 << "W:			Move forward" << std::endl
		 << "A:			Move left" << std::endl
		 << "S:			Move backward" << std::endl
		 << "D:			Move right" << std::endl
		 << "SPACE:		Jump" << std::endl << std::endl
		 << "[Block Interaction]" << std::endl
		 << "Left Mouse Button: Destroy block" << std::endl
		<< "Right Mouse Button: Place block" << std::endl << std::endl
		 << "[Block Switching]" << std::endl
		 << "1:			Switch to GRASS_BLOCK" << std::endl
		 << "2:			Switch to Log" << std::endl
		 << "3:			Switch to Diamond" << std::endl
		<< "4:			Switch to Smooth Stone" << std::endl
		<< "5:			Switch to Stone" << std::endl<<
		"i/I::		Increase/Decrease diffuse" << std::endl <<
		"o/O:		Increase/Decrease the specular" << std::endl << std::endl<<
		"j/J:		Increase/Decrease the light_x" << std::endl <<
		"k/K:		Increase/Decrease light_y" << std::endl <<
		"l/L:		Increase/Decrease the light_z" << std::endl <<
		"p/P:		Increase/Decrease move_step" << std::endl ;
}

bool	Stand=true;
bool	Walk_forward = false;
bool	Walk_back = false;
bool	Walk_left = false;
bool	Walk_right = false;
bool	Jump = false;

float walkTimer = 0.0f;
float jumpTimer = 0.0f;

// 键盘响应函数
void key_callback(GLFWwindow* window, int key, int scancode, int action, int mode)
{
	float tmp;
	glm::vec4 ambient;
	glm::vec4 diffuse;
	glm::vec4 specular;
	glm::vec3 pos;

	if (action == GLFW_PRESS && mode == 0x0000) {
		switch (key)
		{
		case GLFW_KEY_ESCAPE: exit(EXIT_SUCCESS); break;
		case GLFW_KEY_H: printHelp(); break;
		case GLFW_KEY_U:ambient = light->getAmbient();
			light->setAmbient(glm::vec4(ambient.x + 0.2, ambient.y + 0.2, ambient.z + 0.2, ambient.w)); break;
		case GLFW_KEY_I: diffuse = light->getDiffuse();
			light->setDiffuse(glm::vec4(diffuse.x + 0.2, diffuse.y + 0.2, diffuse.z + 0.2, diffuse.w)); break;
		case GLFW_KEY_O: specular = light->getSpecular();
			light->setSpecular(glm::vec4(specular.x + 0.2, specular.y + 0.2, specular.z + 0.2, specular.w)); break;
		case GLFW_KEY_J:sunTranslation.x += move_step_size; light->setTranslation(sunTranslation); sun->setTranslation(sunTranslation); break;
		case GLFW_KEY_K:sunTranslation.y += move_step_size; light->setTranslation(sunTranslation); sun->setTranslation(sunTranslation); break;
		case GLFW_KEY_L:sunTranslation.z += move_step_size; light->setTranslation(sunTranslation); sun->setTranslation(sunTranslation); break;
		case GLFW_KEY_P:move_step_size += 0.1; break;
		case GLFW_KEY_W: Walk_forward=true; Stand = false; break;
		case GLFW_KEY_S: Walk_back = true; Stand = false; break;
		case GLFW_KEY_A: Walk_left = true; Stand = false; break;
		case GLFW_KEY_D:  Walk_right = true; Stand = false; break;
		case GLFW_KEY_SPACE: Jump = true; Stand = false; break;
		case GLFW_KEY_F5:robot.radius_3 = 0; break;
		case GLFW_KEY_F6:robot.radius_3 = 4.0f; break;
		case GLFW_KEY_1:current_type = GRASS_BLOCK; 	painter->removeMesh(hand_block); hand_block = new TriMesh(); hand_block->generateBlock();hand_block->name = "HandBlock";painter->addMesh(hand_block, path[current_type], vshader, fshader); break;
		case GLFW_KEY_2:current_type = Log; painter->removeMesh(hand_block); hand_block = new TriMesh(); hand_block->generateBlock(); hand_block->name = "HandBlock"; painter->addMesh(hand_block, path[current_type], vshader, fshader); break;
		case GLFW_KEY_3:current_type = diamond;	painter->removeMesh(hand_block); hand_block = new TriMesh(); hand_block->generateBlock(); hand_block->name = "HandBlock"; painter->addMesh(hand_block, path[current_type], vshader, fshader); break;
		case GLFW_KEY_4:current_type = smooth_stone; painter->removeMesh(hand_block); hand_block = new TriMesh(); hand_block->generateBlock(); hand_block->name = "HandBlock"; painter->addMesh(hand_block, path[current_type], vshader, fshader); break;
		case GLFW_KEY_5:current_type = stone; painter->removeMesh(hand_block); hand_block = new TriMesh(); hand_block->generateBlock(); hand_block->name = "HandBlock"; painter->addMesh(hand_block, path[current_type], vshader, fshader); break;
		default:break;
		}
	}
	if (action == GLFW_RELEASE && mode == 0x0000) {
		switch (key)
		{
		case GLFW_KEY_W:Stand = true; Walk_forward = false; break;
		case GLFW_KEY_S: Stand = true; Walk_back = false;  break;
		case GLFW_KEY_A: Stand = true; Walk_left = false; break;
		case GLFW_KEY_D: Stand = true; Walk_right = false;  break;
		}
	}
	if (action == GLFW_PRESS && mode == GLFW_MOD_SHIFT) {
		switch (key)
		{
		case GLFW_KEY_ESCAPE: exit(EXIT_SUCCESS); break;
		case GLFW_KEY_H: printHelp(); break;
		case GLFW_KEY_U:ambient = light->getAmbient();
			light->setAmbient(glm::vec4(ambient.x - 0.2, ambient.y - 0.2, ambient.z - 0.2, ambient.w)); break;
		case GLFW_KEY_I: diffuse = light->getDiffuse();
			light->setDiffuse(glm::vec4(diffuse.x - 0.2, diffuse.y - 0.2, diffuse.z - 0.2, diffuse.w)); break;
		case GLFW_KEY_O: specular = light->getSpecular();
			light->setSpecular(glm::vec4(specular.x - 0.2, specular.y - 0.2, specular.z - 0.2, specular.w)); break;
		case GLFW_KEY_J:sunTranslation.x -= move_step_size; light->setTranslation(sunTranslation); sun->setTranslation(sunTranslation); break;
		case GLFW_KEY_K:sunTranslation.y -= move_step_size; light->setTranslation(sunTranslation); sun->setTranslation(sunTranslation); break;
		case GLFW_KEY_L:sunTranslation.z -= move_step_size; light->setTranslation(sunTranslation); sun->setTranslation(sunTranslation); break;
		case GLFW_KEY_P:move_step_size -= 0.1; break;
		default:break;
		}
	}

}
bool firstMouse = true;
float lastx=600.0f/2.0;
float lasty=600.0f/2.0;
void mouse_callback(GLFWwindow* window, double xpos,double ypos)
{
	if (firstMouse) {
		lastx = xpos;
		lasty = ypos;
		firstMouse = false;
	}
	float xoffset = xpos - lastx;
	float yoffset = ypos - lasty;
	lastx = xpos;
	lasty = ypos;
	float sensitivity = 0.1f;
	xoffset *= sensitivity;
	yoffset *= sensitivity;
	robot.angle -= xoffset;
	robot.up_angel-= yoffset;
	if (robot.up_angel >= 90.0f) {
		robot.up_angel = 89.9f;
	}
	if (robot.up_angel <= -90.0f) {
		robot.up_angel = -89.9f;
	}

}
bool check_is_close(int x, int y, int z) {
	// 检查方块周围的六个面是否有其他方块
	// 检查x轴方向
	bool left = (x > 0) && is_exis[x - 1][y][z];
	bool right = (x < world_size - 1) && is_exis[x + 1][y][z];
	// 检查y轴方向
	bool bottom = (y > 0) && is_exis[x][y - 1][z];
	bool top = (y < world_size / 2 - 1) && is_exis[x][y + 1][z];
	// 检查z轴方向
	bool front = (z > 0) && is_exis[x][y][z - 1];
	bool back = (z < world_size - 1) && is_exis[x][y][z + 1];

	// 如果任何一个方向上有方块，则返回true
	return left || right || bottom || top || front || back;
}

void mouse_button_callback(GLFWwindow* window, int button, int action, int mods) {
	if (button == GLFW_MOUSE_BUTTON_LEFT && action == GLFW_PRESS) {
		glm::vec3 xyz = getblock_xyz();
		int x = xyz.x, y = xyz.y, z = xyz.z;
		if (xyz == glm::vec3(-1, -1, -1)) {
			return;
		}
		deal_pointers(x, y, z);
		Destoryblock( x, y, z);
	}
	if (button == GLFW_MOUSE_BUTTON_RIGHT && action == GLFW_PRESS) {
		glm::vec3 xyz = getEmptySpace_xyz();
		int x = xyz.x, y = xyz.y, z = xyz.z;
		deal_pointers(x, y, z);
		int x1 = robot.robot_location.x, y1 = robot.robot_location.y, z1 = robot.robot_location.z;
		if (xyz == glm::vec3(-1, -1, -1)) {
			return;
		}
		//
			AABB blockAABB = {
						glm::vec3(x  - 0.5f, 0.0f, z - 0.5f),
						glm::vec3(x  + 0.5f, 0.0f, z + 0.5f)
			};
			AABB robotAABB = {
				glm::vec3(robot.robot_location.x - robot.collision_size, 0.0f, robot.robot_location.z - robot.collision_size),
				glm::vec3(robot.robot_location.x + robot.collision_size, 0.0f, robot.robot_location.z + robot.collision_size)
			};
		bool iscollision = is_collision(blockAABB, robotAABB);
		if (check_is_close(x, y, z) && !iscollision) {
			drawblock(current_type, x, y, z);
		}
	}
}
// 重新设置窗口
void reshape(GLsizei w, GLsizei h)
{
	glViewport(0, 0, w, h);
}

void cleanData() {
	// 释放内存
	
	delete camera;
	camera = NULL;

	delete light;
	light = NULL;

	painter->cleanMeshes();

	delete painter;
	painter = NULL;
	
	for (int i=0; i<meshList.size(); i++) {
		delete meshList[i];
	}
	meshList.clear();
}
//跳跃
void update_h() {
	//判断底下一格是否有方块，如果有则更新current_h，否则为下一格
	glm::vec3 xyz = get_xyz(robot.robot_location);
	int x = xyz.x, y = xyz.y, z = xyz.z;
	if (is_exis[x][y][z]) {
		robot.current_h = y;
	}
	else {
		robot.current_h = y - 1;
	}
}
void framebuffer_size_callback(GLFWwindow* window, int width, int height);
float angleSpeed = 45.0f; // 每秒旋转的角度
bool firstjump=true;

void checkDrop(float deltaTime) {
	update_h();
	//跳跃轨迹
	robot.robot_location.y += robot.v_up * deltaTime - 0.5f * robot.g * deltaTime * deltaTime;
	robot.v_up -= robot.g * deltaTime;
	glm::vec3 xyz = get_xyz(robot.robot_location);
	int x = xyz.x, y = xyz.y, z = xyz.z;
	// 当机器人落地时，重置跳跃计时器和位置
	if (robot.robot_location.y <= robot.current_h && robot.v_up < 0) {
		robot.robot_location.y = robot.current_h;
		robot.v_up = 0.0f;
		firstjump = true;
		Jump = false;
	}
	//头顶有方块时
	if (is_exis[x][y + 3][z] && robot.v_up > 0&& robot.robot_location.y>=y+0.2) {
		robot.v_up = 0.0f;
	}
	
}
void updateRobotMovement(float deltaTime) {
	robot.last_location = robot.robot_location;
	robot.speed = 2.0f;
	
	float x, z;
	//如果运动了则摆臂
		if (Walk_forward|| Walk_back || Walk_left || Walk_right) {
			walkTimer += deltaTime * 0.1;
			robot.theta[robot.RightUpperArm] = std::sin(walkTimer * angleSpeed) * 45.0f;
			robot.theta[robot.LeftUpperArm] = -std::sin(walkTimer * angleSpeed) * 45.0f;
			robot.theta[robot.RightUpperLeg] = std::sin(walkTimer * angleSpeed) * 45.0f;
			robot.theta[robot.LeftUpperLeg] = -std::sin(walkTimer * angleSpeed) * 45.0f;		
		}
		else {
			// 机器人不走动
			for (int i = 0; i < 10; ++i) {
				robot.theta[i] = 0.0f;
			}
			walkTimer = 0;
		}
		//计算直走的位移
		if (Walk_forward) {
					
			robot.robot_location.x += robot.velocity.x * robot.speed * deltaTime;
			robot.robot_location.z += robot.velocity.z * robot.speed * deltaTime;
		}
		//计算后退的位移
		if (Walk_back) {
			robot.robot_location.x -= robot.velocity.x * robot.speed * deltaTime;
			robot.robot_location.z -= robot.velocity.z * robot.speed * deltaTime;
		}
		//计算左走的位移
		if (Walk_left) {
			x = glm::sin(glm::radians(robot.angle + 90));
			z = glm::cos(glm::radians(robot.angle + 90));
			robot.robot_location.x += x * robot.speed * deltaTime;
			robot.robot_location.z += z * robot.speed * deltaTime;
		}
		//计算右走的位移
		if (Walk_right) {
			x = glm::sin(glm::radians(robot.angle - 90));
			z = glm::cos(glm::radians(robot.angle - 90));
			robot.robot_location.x += x * robot.speed * deltaTime;
			robot.robot_location.z += z * robot.speed * deltaTime;
		}
		//设置跳跃
		if (Jump) {
			if (firstjump) {
				robot.v_up = 5.0f;
				firstjump = false;
			}
		}
		CheckAABBCollision();
		checkDrop(deltaTime);
}


int main(int argc, char **argv)
{
	// 初始化GLFW库，必须是应用程序调用的第一个GLFW函数
	glfwInit();

	// 配置GLFW
	glfwWindowHint(GLFW_CONTEXT_VERSION_MAJOR, 3);
	glfwWindowHint(GLFW_CONTEXT_VERSION_MINOR, 3);
	glfwWindowHint(GLFW_OPENGL_PROFILE, GLFW_OPENGL_CORE_PROFILE);

#ifdef __APPLE__
	glfwWindowHint(GLFW_OPENGL_FORWARD_COMPAT, GL_TRUE);
#endif

	// 配置窗口属性
	GLFWwindow* window = glfwCreateWindow(600, 600, "2022150146_hjc_MC", NULL, NULL);
	if (window == NULL)
	{
		std::cout << "Failed to create GLFW window" << std::endl;
		glfwTerminate();
		return -1;
	}
	glfwMakeContextCurrent(window);
	glfwSetKeyCallback(window, key_callback);
	glfwSetCursorPosCallback(window, mouse_callback);
	glfwSetFramebufferSizeCallback(window, framebuffer_size_callback);
	glfwSetMouseButtonCallback(window, mouse_button_callback);
	glfwSetInputMode(window, GLFW_CURSOR, GLFW_CURSOR_DISABLED);
	// 调用任何OpenGL的函数之前初始化GLAD
	// ---------------------------------------
	if (!gladLoadGLLoader((GLADloadproc)glfwGetProcAddress))
	{
		std::cout << "Failed to initialize GLAD" << std::endl;
		return -1;
	}

	// Init mesh, shaders, buffer
	init();
	// 输出帮助信息
	printHelp();
	 //启用深度测试
	glEnable(GL_DEPTH_TEST);
	glEnable(GL_CULL_FACE);
	// 设置剔除背面
	glCullFace(GL_BACK);
	// 渲染循环
	while (!glfwWindowShouldClose(window))
	{
		// 计算自上一帧以来的时间差
		static float lastFrame = glfwGetTime();
		float currentFrame = glfwGetTime();
		float deltaTime = currentFrame - lastFrame;
		lastFrame = currentFrame;
	
		// 更新机器人运动
		updateRobotMovement(deltaTime);
		
		display();

		glfwSwapBuffers(window);
		glfwPollEvents();
		
	}

	cleanData();


	return 0;
}

// 每当窗口改变大小，GLFW会调用这个函数并填充相应的参数供你处理。
// ---------------------------------------------------------------------------------------------
void framebuffer_size_callback(GLFWwindow * window, int width, int height)
{
	// make sure the viewport matches the new window dimensions; note that width and 
	// height will be significantly larger than specified on retina displays.
	glViewport(0, 0, width, height);
}
