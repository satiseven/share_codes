import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Coupon from 'App/Models/Coupon'
import User from 'App/Models/User'
import CreateCouponValidator from 'App/Validators/Coupon/CreateCouponValidator'
import GetCouponByIdValidator from 'App/Validators/Coupon/GetCouponByIdValidator'
import UpdateCouponValidator from 'App/Validators/Coupon/UpdateCouponValidator'

export default class CouponController {
  public async index({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const coupons = await Coupon.query().where({ userId: user.id })

    return {
      data: {
        coupons: coupons.map((coupon) => coupon.serializedCouponInfo),
      },
    }
  }

  public async show({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetCouponByIdValidator)
    const coupon = await Coupon.query().where({ id: params.id, userId: user.id }).firstOrFail()

    return {
      data: {
        coupon: coupon.serializedCouponInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { code, percentage, isRepetitive } = await request.validate(CreateCouponValidator)
    const coupon = await Coupon.create({
      code,
      percentage,
      isRepetitive,
      userId: user.id,
    })

    return {
      data: {
        message: 'Coupon created successfully',
        coupon: coupon.serializedCouponInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetCouponByIdValidator)
    const coupon = await Coupon.query().where({ id: params.id, userId: user.id }).firstOrFail()
    const updateAttributes = await request.validate(UpdateCouponValidator)
    const updatedCoupon = await coupon.merge(updateAttributes).save()

    return {
      data: {
        message: 'Coupon updated successfully',
        coupon: updatedCoupon.serializedCouponInfo,
      },
    }
  }

  public async destroy({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { params } = await request.validate(GetCouponByIdValidator)
    const coupon = await Coupon.query().where({ id: params.id, userId: user.id }).firstOrFail()

    await coupon.delete()

    return {
      data: {
        message: 'Coupon deleted successfully',
      },
    }
  }
}
