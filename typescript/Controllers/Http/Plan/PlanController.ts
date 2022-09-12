import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Plan from 'App/Models/Plan'
import CreatePlanValidator from 'App/Validators/Plan/CreatePlanValidator'
import GetPlanByIdValidator from 'App/Validators/Plan/GetPlanByIdValidator'
import UpdatePlanValidator from 'App/Validators/Plan/UpdatePlanValidator'

export default class PlanController {
  public async index() {
    const plans = await Plan.query()

    return {
      data: {
        plans: plans.map((plan) => plan.serializedPlanInfo),
      },
    }
  }

  public async show({ request }: HttpContextContract) {
    const { params } = await request.validate(GetPlanByIdValidator)
    const plan = await Plan.query().where({ id: params.id }).firstOrFail()

    return {
      data: {
        plan: plan.serializedPlanInfo,
      },
    }
  }

  public async create({ request }: HttpContextContract) {
    const { name, price, isActive } = await request.validate(CreatePlanValidator)
    const plan = await Plan.create({ name, price, isActive })

    return {
      data: {
        message: 'Plan created successfully',
        plan: plan.serializedPlanInfo,
      },
    }
  }

  public async update({ request }: HttpContextContract) {
    const { params } = await request.validate(GetPlanByIdValidator)
    const plan = await Plan.query().where({ id: params.id }).firstOrFail()

    const { name, price, isActive } = await request.validate(UpdatePlanValidator)
    const updatedPlan = await plan.merge({ name, price, isActive }).save()

    return {
      data: {
        message: 'Plan updated successfully',
        plan: updatedPlan.serializedPlanInfo,
      },
    }
  }

  public async destroy({ request }: HttpContextContract) {
    const { params } = await request.validate(GetPlanByIdValidator)
    const plan = await Plan.query().where({ id: params.id }).firstOrFail()

    await plan.delete()

    return {
      data: {
        message: 'Plan deleted successfully',
      },
    }
  }
}
